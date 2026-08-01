<?php

namespace App\Services;

use App\Invoice;
use App\MedicalService;
use App\Prescription;
use App\PrescriptionItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionService
{
    /**
     * Get all prescriptions for the list-all page.
     */
    public function getAllPrescriptions(): Collection
    {
        return DB::table('prescriptions')
            ->leftJoin('patients', 'patients.id', 'prescriptions.patient_id')
            ->leftJoin('users', 'users.id', 'prescriptions.doctor_id')
            ->leftJoin('invoices', 'invoices.id', 'prescriptions.invoice_id')
            ->whereNull('prescriptions.deleted_at')
            ->orderBy('prescriptions.created_at', 'desc')
            ->select(
                'prescriptions.*',
                'patients.patient_no',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(users.surname, users.othername) as doctor_name"
                    : "CONCAT(users.surname, ' ', users.othername) as doctor_name"),
                'invoices.invoice_no',
                'invoices.payment_status'
            )
            ->get();
    }

    /**
     * Get prescriptions for a specific patient (patient detail tab).
     */
    public function getPatientPrescriptions(int $patientId): Collection
    {
        return DB::table('prescriptions')
            ->leftJoin('users', 'users.id', 'prescriptions.doctor_id')
            ->leftJoin('invoices', 'invoices.id', 'prescriptions.invoice_id')
            ->whereNull('prescriptions.deleted_at')
            ->where('prescriptions.patient_id', $patientId)
            ->orderBy('prescriptions.created_at', 'desc')
            ->select(
                'prescriptions.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(users.surname, users.othername) as doctor_name"
                    : "CONCAT(users.surname, ' ', users.othername) as doctor_name"),
                'invoices.invoice_no',
                'invoices.payment_status'
            )
            ->get();
    }

    /**
     * Get prescriptions for a specific appointment (legacy).
     */
    public function getPrescriptionsByAppointment(int $appointmentId): Collection
    {
        return Prescription::where('appointment_id', $appointmentId)->get();
    }

    /**
     * Get available prescription services (medical_services where is_prescription=true).
     */
    public function getPrescriptionServices(): Collection
    {
        return MedicalService::where('is_prescription', true)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->select('id', 'name', 'price', 'category', 'unit')
            ->get();
    }

    /**
     * Get all unique drug names for autocomplete (legacy).
     */
    public function getAllDrugNames(): array
    {
        return Prescription::select('drug')->whereNotNull('drug')->get()->pluck('drug')->unique()->values()->toArray();
    }

    /**
     * Create a prescription with items.
     */
    public function createPrescription(array $data, array $items): array
    {
        if (empty($items)) {
            return ['status' => false, 'message' => __('prescriptions.no_items')];
        }

        DB::beginTransaction();
        try {
            $prescription = $this->buildPrescription($data, $items);

            DB::commit();

            return [
                'status' => true,
                'message' => __('messages.prescription_created_successfully'),
                'prescription_id' => $prescription->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrescriptionService::createPrescription failed', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred')];
        }
    }

    /**
     * AG-024: Save prescription and auto-create Invoice (保存并结算).
     * Idempotent — will not create duplicate Invoice if prescription already has one.
     */
    public function saveAndSettle(array $data, array $items): array
    {
        if (empty($items)) {
            return ['status' => false, 'message' => __('prescriptions.no_items')];
        }

        DB::beginTransaction();
        try {
            $prescription = $this->buildPrescription($data, $items);
            $prescription->load('items');

            // Create Invoice
            $invoiceResult = $this->createInvoiceFromPrescription($prescription);
            if (!$invoiceResult['status']) {
                DB::rollBack();
                return $invoiceResult;
            }

            // Update prescription status and link invoice
            $prescription->update([
                'status'     => 'filled',
                'invoice_id' => $invoiceResult['invoice_id'],
            ]);

            DB::commit();

            return [
                'status' => true,
                'message' => __('prescriptions.saved_and_settled'),
                'prescription_id' => $prescription->id,
                'invoice_id' => $invoiceResult['invoice_id'],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrescriptionService::saveAndSettle failed', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred')];
        }
    }

    /**
     * Settle an existing pending prescription (从划价结算页面选择处方收费).
     * AG-024: Idempotent — skip if already settled.
     */
    public function settlePrescription(int $prescriptionId): array
    {
        $prescription = Prescription::with('items')->findOrFail($prescriptionId);

        if ($prescription->invoice_id) {
            return ['status' => false, 'message' => __('prescriptions.already_settled')];
        }

        DB::beginTransaction();
        try {
            $invoiceResult = $this->createInvoiceFromPrescription($prescription);
            if (!$invoiceResult['status']) {
                DB::rollBack();
                return $invoiceResult;
            }

            $prescription->update([
                'status'     => 'filled',
                'invoice_id' => $invoiceResult['invoice_id'],
            ]);

            DB::commit();

            return [
                'status' => true,
                'message' => __('prescriptions.settled_successfully'),
                'invoice_id' => $invoiceResult['invoice_id'],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrescriptionService::settlePrescription failed', ['id' => $prescriptionId, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred')];
        }
    }

    /**
     * Get a single prescription with items for editing.
     */
    public function getPrescriptionForEdit(int $id): ?Prescription
    {
        return Prescription::with('items.medicalService')->find($id);
    }

    /**
     * Get prescription detail with all relations.
     */
    public function getPrescriptionDetail(int $id): ?Prescription
    {
        return Prescription::with(['items.medicalService', 'patient', 'doctor', 'invoice'])->find($id);
    }

    /**
     * Update a prescription and its items.
     */
    public function updatePrescription(int $id, array $data, array $items = []): array
    {
        $prescription = Prescription::findOrFail($id);

        // Block modifications on settled prescriptions
        if ($prescription->invoice_id) {
            return ['status' => false, 'message' => __('prescriptions.cannot_edit_settled')];
        }

        DB::beginTransaction();
        try {
            $prescription->update([
                'doctor_id'         => $data['doctor_id'] ?? $prescription->doctor_id,
                'prescription_date' => $data['prescription_date'] ?? $prescription->prescription_date,
                'notes'             => $data['notes'] ?? $prescription->notes,
            ]);

            // Replace items if provided
            if (!empty($items)) {
                PrescriptionItem::where('prescription_id', $id)->delete();
                $this->createItems($id, $items);
            }

            DB::commit();

            return ['status' => true, 'message' => __('messages.prescription_updated_successfully')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrescriptionService::updatePrescription failed', ['id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred')];
        }
    }

    /**
     * AG-023: Delete prescription — only if no linked Invoice.
     */
    public function deletePrescription(int $id): array
    {
        $prescription = Prescription::findOrFail($id);

        if (!$prescription->is_deletable) {
            return [
                'status' => false,
                'message' => __('prescriptions.cannot_delete_has_invoice'),
            ];
        }

        $prescription->delete();

        return ['status' => true, 'message' => __('messages.prescription_deleted_successfully')];
    }

    /**
     * Get pending (unsettled) prescriptions for a patient — for billing page selection.
     */
    public function getPendingPrescriptions(int $patientId): Collection
    {
        return Prescription::with('items.medicalService')
            ->where('patient_id', $patientId)
            ->whereNull('invoice_id')
            ->where('status', 'pending')
            ->orderBy('prescription_date', 'desc')
            ->get();
    }

    /**
     * Get prescription print data.
     */
    public function getPrintData(int $prescriptionId): array
    {
        $prescription = Prescription::with(['items.medicalService', 'patient', 'doctor'])->findOrFail($prescriptionId);

        return [
            'prescription' => $prescription,
            'patient' => $prescription->patient,
            'doctor' => $prescription->doctor,
        ];
    }

    /**
     * Legacy: Get print data by appointment.
     */
    public function getPrintDataByAppointment(int $appointmentId): array
    {
        $patient = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $appointmentId)
            ->select('patients.*')
            ->first();

        $prescriptions = Prescription::where('appointment_id', $appointmentId)->get();

        $prescribed_by = DB::table('prescriptions')
            ->join('users', 'users.id', 'prescriptions._who_added')
            ->whereNull('prescriptions.deleted_at')
            ->where('prescriptions.appointment_id', $appointmentId)
            ->select('users.*')
            ->first();

        return compact('patient', 'prescriptions', 'prescribed_by');
    }

    /**
     * Legacy: Create multiple simple prescriptions for an appointment.
     */
    public function createPrescriptions(int $appointmentId, array $items): void
    {
        foreach ($items as $value) {
            Prescription::create([
                'drug' => $value['drug'],
                'qty' => $value['qty'],
                'directions' => $value['directions'],
                'appointment_id' => $appointmentId,
                '_who_added' => Auth::id(),
            ]);
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Build a Prescription record with its items (shared by create & saveAndSettle).
     * Must be called inside a DB transaction.
     */
    private function buildPrescription(array $data, array $items): Prescription
    {
        $attrs = [
            'prescription_no'   => Prescription::generatePrescriptionNo(),
            'patient_id'        => $data['patient_id'],
            'doctor_id'         => $data['doctor_id'] ?? Auth::id(),
            'appointment_id'    => $data['appointment_id'] ?? null,
            'medical_case_id'   => $data['medical_case_id'] ?? null,
            'prescription_date' => $data['prescription_date'] ?? now()->format('Y-m-d'),
            'status'            => 'pending',
            'notes'             => $data['notes'] ?? null,
            '_who_added'        => Auth::id(),
        ];

        try {
            $prescription = Prescription::create($attrs);
        } catch (QueryException $e) {
            // Retry once with next number on duplicate key (race condition)
            if ($e->errorInfo[1] == 1062) {
                $attrs['prescription_no'] = Prescription::generatePrescriptionNo();
                $prescription = Prescription::create($attrs);
            } else {
                throw $e;
            }
        }

        $this->createItems($prescription->id, $items);

        return $prescription;
    }

    /**
     * Create prescription items from input array.
     * AG-025: unit_price is fetched from medical_services, not from frontend.
     */
    private function createItems(int $prescriptionId, array $items): void
    {
        foreach ($items as $item) {
            $unitPrice = null;
            $drugName = $item['drug_name'] ?? '';

            // AG-025: Fetch price from medical_services
            if (!empty($item['medical_service_id'])) {
                $service = MedicalService::find($item['medical_service_id']);
                if ($service) {
                    $unitPrice = $service->price;
                    $drugName = $drugName ?: $service->name;
                }
            }

            PrescriptionItem::create([
                'prescription_id'    => $prescriptionId,
                'medical_service_id' => $item['medical_service_id'] ?? null,
                'drug_name'          => $drugName,
                'dosage'             => $item['dosage'] ?? null,
                'quantity'           => max(1, (int) ($item['quantity'] ?? 1)), // AG-026
                'unit_price'         => $unitPrice,
                'frequency'          => $item['frequency'] ?? null,
                'duration'           => $item['duration'] ?? null,
                'usage'              => $item['usage'] ?? null,
                'notes'              => $item['notes'] ?? null,
                'inventory_item_id'  => $item['inventory_item_id'] ?? null,
                '_who_added'         => Auth::id(),
            ]);
        }
    }

    /**
     * Create an Invoice from prescription items.
     */
    private function createInvoiceFromPrescription(Prescription $prescription): array
    {
        $invoiceItems = [];
        foreach ($prescription->items as $item) {
            $price = $item->unit_price ?? 0;
            $lineTotal = bcmul((string) $price, (string) $item->quantity, 2);

            $invoiceItems[] = [
                'medical_service_id' => $item->medical_service_id,
                'qty'                => $item->quantity,
                'price'              => $price,
                'discount_rate'      => 100,
                'discounted_price'   => $lineTotal,
                'actual_paid'        => 0,
                'arrears'            => $lineTotal,
                'doctor_id'          => $prescription->doctor_id,
            ];
        }

        if (empty($invoiceItems)) {
            return ['status' => false, 'message' => __('prescriptions.no_items')];
        }

        $invoiceService = app(InvoiceService::class);

        return $invoiceService->createBillingInvoice(
            $prescription->patient_id,
            $invoiceItems,
            [],                // no payments — unpaid
            100,               // no order discount
            $prescription->prescription_date?->format('Y-m-d'),
            'front_desk'       // pending payment
        );
    }
}
