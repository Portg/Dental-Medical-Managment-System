<?php

namespace App\Services;

use App\Invoice;
use App\InvoiceItem;
use App\MedicalService;
use App\Patient;
use App\User;
use App\WorkLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class WorkLogService
{
    /**
     * Recognize a work-log table image via the dedicated Python script.
     *
     * @return array{header_found: bool, columns: array, rows: array, avg_confidence: float, raw_lines: array}
     * @throws \RuntimeException
     */
    public function recognize(string $imagePath): array
    {
        if (!file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        $pythonPath = config('services.ocr.python_path', 'python3');
        $scriptPath = config('services.ocr.worklog_script_path', base_path('scripts/worklog_ocr.py'));
        $timeout    = (int) config('services.ocr.timeout', 60);

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("Work-log OCR script not found: {$scriptPath}");
        }

        $process = new Process([$pythonPath, $scriptPath, $imagePath]);
        $process->setTimeout($timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new \RuntimeException("OCR recognition failed: " . $process->getErrorOutput());
        }

        $result = json_decode($process->getOutput(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('OCR output is not valid JSON: ' . json_last_error_msg());
        }

        return [
            'header_found'   => (bool) ($result['header_found'] ?? false),
            'columns'        => $result['columns'] ?? [],
            'rows'           => $result['rows'] ?? [],
            'avg_confidence' => $result['avg_confidence'] ?? 0,
            'raw_lines'      => $result['raw_lines'] ?? [],
        ];
    }

    /**
     * Match an existing patient by phone (preferred) then exact full name.
     */
    public function matchPatient(?string $name, ?string $phone): ?Patient
    {
        $phone = $phone ? trim($phone) : null;
        if ($phone && preg_match('/1[3-9]\d{9}/', $phone, $m)) {
            $patient = Patient::whereNull('deleted_at')
                ->where('phone_no', $m[0])
                ->first();
            if ($patient) {
                return $patient;
            }
        }

        $name = $name ? trim($name) : null;
        if ($name && mb_strlen($name) >= 2) {
            return Patient::whereNull('deleted_at')
                ->whereRaw("CONCAT(COALESCE(surname,''), COALESCE(othername,'')) = ?", [$name])
                ->first();
        }

        return null;
    }

    /**
     * Match a doctor user by full name or surname.
     */
    public function matchDoctor(?string $name): ?int
    {
        $name = $name ? trim($name) : null;
        if (!$name) {
            return null;
        }

        $query = User::where('is_doctor', true)
            ->whereNull('deleted_at')
            ->where('status', User::STATUS_ACTIVE);

        $doctor = (clone $query)
            ->whereRaw("CONCAT(COALESCE(surname,''), COALESCE(othername,'')) = ?", [$name])
            ->first();

        if (!$doctor && mb_strlen($name) <= 2) {
            // Short scrawled name often only captures the surname.
            $doctor = (clone $query)->where('surname', $name)->first();
        }

        return optional($doctor)->id;
    }

    /**
     * Find or create the sentinel medical service used for work-log
     * back-fill invoice line items. Inactive so it never shows in the
     * normal billing service picker.
     */
    public function workLogService(): MedicalService
    {
        return MedicalService::firstOrCreate(
            ['name' => '工作日志补录'],
            [
                'unit'            => '次',
                'price'           => 0,
                'category'        => '其他',
                'description'     => '工作日志台账补录自动生成项目',
                'is_active'       => false,
                'is_prescription' => false,
                '_who_added'      => Auth::id(),
            ]
        );
    }

    /**
     * Create a lightweight unpaid invoice for a work-log row.
     * Deliberately bypasses InvoiceService::createBillingInvoice:
     * no stock-out, no payment, no discount approval side effects.
     */
    public function createLightInvoice(int $patientId, string $amount, ?int $doctorId, ?string $invoiceDate): int
    {
        $service = $this->workLogService();
        $amount  = number_format((float) $amount, 2, '.', '');

        $invoice = Invoice::create([
            'invoice_no'         => Invoice::InvoiceNo(),
            'invoice_date'       => $invoiceDate ?: now()->format('Y-m-d'),
            'patient_id'         => $patientId,
            'subtotal'           => $amount,
            'discount_amount'    => 0,
            'total_amount'       => $amount,
            'paid_amount'        => 0,
            'outstanding_amount' => $amount,
            'payment_status'     => Invoice::PAYMENT_UNPAID,
            'billing_mode'       => 'work_log',
            'doctor_id'          => $doctorId,
            'notes'              => '工作日志台账补录',
            '_who_added'         => Auth::id(),
        ]);

        InvoiceItem::create([
            'invoice_id'         => $invoice->id,
            'medical_service_id' => $service->id,
            'qty'                => 1,
            'price'              => $amount,
            'discount_rate'      => 100,
            'discounted_price'   => $amount,
            'actual_paid'        => 0,
            'arrears'            => $amount,
            'doctor_id'          => $doctorId,
            '_who_added'         => Auth::id(),
        ]);

        return $invoice->id;
    }

    /**
     * Batch-persist reviewed work-log rows in a single transaction.
     * Any invalid row aborts and rolls back the whole batch.
     *
     * @param array $rows    Reviewed rows from the editable grid
     * @param array $options ['link_patients'=>bool,'generate_invoices'=>bool,'source_image'=>?string]
     * @return array{batch_id:string,created:int,linked:int,invoiced:int}
     * @throws \RuntimeException with the offending 1-based row number
     */
    public function batchStore(array $rows, array $options = []): array
    {
        $linkPatients     = (bool) ($options['link_patients'] ?? false);
        $generateInvoices = (bool) ($options['generate_invoices'] ?? false);
        $generateCases    = (bool) ($options['generate_cases'] ?? false);
        $sourceImage      = $options['source_image'] ?? null;
        $batchId          = (string) Str::uuid();

        $created  = 0;
        $linked   = 0;
        $invoiced = 0;
        $cased    = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $rowNo = $i + 1;

                $name = isset($row['patient_name']) ? trim((string) $row['patient_name']) : '';
                if ($name === '') {
                    throw new \RuntimeException(
                        __('work_log.row_name_required', ['row' => $rowNo])
                    );
                }

                $logDate = $this->normalizeDate(
                    $row['log_date'] ?? null,
                    $options['year'] ?? null
                );

                $phone     = $this->cleanPhone($row['phone'] ?? null);
                $amountRaw = isset($row['amount']) ? preg_replace('/[^\d.]/', '', (string) $row['amount']) : '';
                $amount    = ($amountRaw !== '' && (float) $amountRaw > 0) ? $amountRaw : null;

                $visitType = $this->normalizeVisitType($row['visit_type'] ?? null);
                $doctorId  = $this->matchDoctor($row['doctor_name_raw'] ?? null);

                $patientId = null;
                if ($linkPatients) {
                    $patient = $this->matchPatient($name, $phone);
                    if ($patient) {
                        $patientId = $patient->id;
                        $linked++;
                    }
                }

                $invoiceId = null;
                if ($generateInvoices && $amount !== null && $patientId) {
                    $invoiceId = $this->createLightInvoice(
                        $patientId,
                        $amount,
                        $doctorId,
                        $logDate
                    );
                    $invoiced++;
                }

                $caseId = null;
                if ($generateCases && $patientId !== null) {
                    $caseId = $this->createDraftCase(
                        $patientId,
                        $doctorId,
                        $logDate,
                        $visitType,
                        $row
                    );
                    if ($caseId) {
                        $cased++;
                    }
                }

                WorkLog::create([
                    'log_date'        => $logDate,
                    'patient_name'    => $name,
                    'gender'          => $this->blankToNull($row['gender'] ?? null),
                    'age'             => $this->blankToNull($row['age'] ?? null),
                    'visit_type'      => $visitType,
                    'phone'           => $phone,
                    'tooth_position'  => $this->blankToNull($row['tooth_position'] ?? null),
                    'diagnosis'       => $this->blankToNull($row['diagnosis'] ?? null),
                    'prescription'    => $this->blankToNull($row['prescription'] ?? null),
                    'doctor_id'       => $doctorId,
                    'doctor_name_raw' => $this->blankToNull($row['doctor_name_raw'] ?? null),
                    'amount'          => $amount,
                    'patient_id'      => $patientId,
                    'invoice_id'      => $invoiceId,
                    'medical_case_id' => $caseId,
                    'source_image'    => $sourceImage,
                    'batch_id'        => $batchId,
                    '_who_added'      => Auth::id(),
                ]);

                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }

        return [
            'batch_id' => $batchId,
            'created'  => $created,
            'linked'   => $linked,
            'invoiced' => $invoiced,
            'cased'    => $cased,
        ];
    }

    /**
     * Create a draft MedicalCase from a work-log row.
     * Returns the new case ID, or null on failure.
     */
    private function createDraftCase(
        int $patientId,
        ?int $doctorId,
        ?string $logDate,
        ?string $visitType,
        array $row
    ): ?int {
        $diagnosis = $this->blankToNull($row['diagnosis'] ?? null);
        $title = $diagnosis
            ? mb_substr($diagnosis, 0, 50)
            : (__('work_log.case_from_worklog') . ($logDate ? ' ' . $logDate : ''));

        $case = \App\MedicalCase::create([
            'case_no'        => \App\MedicalCase::CaseNumber(),
            'title'          => $title,
            'case_date'      => $logDate,
            'patient_id'     => $patientId,
            'doctor_id'      => $doctorId,
            'visit_type'     => $visitType ?? \App\WorkLog::VISIT_INITIAL,
            'examination'    => $this->blankToNull($row['tooth_position'] ?? null),
            'diagnosis'      => $diagnosis,
            'treatment'      => $this->blankToNull($row['prescription'] ?? null),
            'status'         => \App\MedicalCase::STATUS_OPEN,
            'is_draft'       => true,
            'version_number' => 1,
            '_who_added'     => Auth::id(),
        ]);

        return optional($case)->id;
    }

    private function normalizeDate($value, ?int $year): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }

        // Already a full date.
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Short "M.D" / "M/D" / "M-D" — combine with the batch year.
        if (preg_match('#^(\d{1,2})[./-](\d{1,2})$#', $value, $m)) {
            $y = $year ?: (int) date('Y');
            try {
                return Carbon::createFromDate($y, (int) $m[1], (int) $m[2])->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeVisitType($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }
        if ($value === WorkLog::VISIT_INITIAL || $value === WorkLog::VISIT_REVISIT) {
            return $value;
        }
        if (mb_strpos($value, '复') !== false) {
            return WorkLog::VISIT_REVISIT;
        }
        if (mb_strpos($value, '初') !== false) {
            return WorkLog::VISIT_INITIAL;
        }
        return null;
    }

    private function cleanPhone($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }
        if (preg_match('/1[3-9]\d{9}/', $value, $m)) {
            return $m[0];
        }
        return $value;
    }

    private function blankToNull($value): ?string
    {
        $value = is_string($value) ? trim($value) : ($value === null ? '' : (string) $value);
        return $value === '' ? null : $value;
    }
}
