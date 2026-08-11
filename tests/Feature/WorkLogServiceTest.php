<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\Patient;
use App\Role;
use App\Services\WorkLogService;
use App\User;
use App\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WorkLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private WorkLogService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);

        $adminRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'status'    => User::STATUS_ACTIVE,
        ]);

        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);
        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => true,
            'status'    => User::STATUS_ACTIVE,
            'surname'   => '李',
            'othername' => '医生',
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        Auth::login($this->admin);
        $this->service = app(WorkLogService::class);
    }

    public function test_batch_store_creates_rows_links_patient_and_generates_invoice(): void
    {
        $result = $this->service->batchStore([
            [
                'log_date'        => '3.24',
                'patient_name'    => '张三',
                'gender'          => '男',
                'age'             => '35',
                'visit_type'      => '初诊',
                'phone'           => '13800138000',
                'tooth_position'  => '16',
                'diagnosis'       => '龋齿',
                'prescription'    => '补牙',
                'doctor_name_raw' => '李医生',
                'amount'          => '收200',
            ],
        ], [
            'link_patients'     => true,
            'generate_invoices' => true,
            'year'              => 2026,
            'source_image'      => 'work_log_images/test.jpg',
        ]);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['linked']);
        $this->assertSame(1, $result['invoiced']);

        $log = WorkLog::first();
        $this->assertNotNull($log);
        $this->assertSame('张三', $log->patient_name);
        $this->assertSame($this->patient->id, $log->patient_id);
        $this->assertSame($this->doctor->id, $log->doctor_id);
        $this->assertSame(WorkLog::VISIT_INITIAL, $log->visit_type);
        $this->assertSame('2026-03-24', $log->log_date->format('Y-m-d'));
        $this->assertEquals('200.00', $log->amount);
        $this->assertNotNull($log->invoice_id);

        $invoice = Invoice::find($log->invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals('200.00', $invoice->total_amount);
        $this->assertEquals('200.00', $invoice->outstanding_amount);
        $this->assertSame(Invoice::PAYMENT_UNPAID, $invoice->payment_status);
        $this->assertEquals(0, (float) $invoice->paid_amount);
    }

    public function test_batch_store_rolls_back_entire_batch_when_a_row_is_invalid(): void
    {
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->batchStore([
                ['patient_name' => '张三', 'amount' => '100'],
                ['patient_name' => '', 'amount' => '300'], // invalid: name required
            ], [
                'link_patients'     => true,
                'generate_invoices' => true,
                'year'              => 2026,
            ]);
        } finally {
            $this->assertSame(0, WorkLog::count());
            $this->assertSame(0, Invoice::count());
        }
    }

    public function test_invoice_is_skipped_when_amount_present_but_no_patient_match(): void
    {
        $result = $this->service->batchStore([
            [
                'patient_name'    => '无名氏',
                'phone'           => '13900000000',
                'amount'          => '500',
                'doctor_name_raw' => '李医生',
            ],
        ], [
            'link_patients'     => true,
            'generate_invoices' => true,
            'year'              => 2026,
        ]);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['linked']);
        $this->assertSame(0, $result['invoiced']);

        $log = WorkLog::first();
        $this->assertNull($log->patient_id);
        $this->assertNull($log->invoice_id);
        $this->assertEquals('500.00', $log->amount);
        $this->assertSame(0, Invoice::count());
    }

    public function test_match_patient_by_phone_and_match_doctor_by_name(): void
    {
        $matched = $this->service->matchPatient('完全不同的名字', '13800138000');
        $this->assertNotNull($matched);
        $this->assertSame($this->patient->id, $matched->id);

        $doctorId = $this->service->matchDoctor('李医生');
        $this->assertSame($this->doctor->id, $doctorId);

        $this->assertNull($this->service->matchDoctor('不存在医生'));
    }

    public function test_store_endpoint_persists_rows(): void
    {
        $response = $this->actingAs($this->admin)->postJson('work-log-ocr/store', [
            'rows' => [
                [
                    'log_date'        => '2026-03-25',
                    'patient_name'    => '张三',
                    'phone'           => '13800138000',
                    'diagnosis'       => '复查',
                    'doctor_name_raw' => '李医生',
                    'amount'          => '150',
                ],
            ],
            'link_patients'     => 1,
            'generate_invoices' => 1,
            'year'              => 2026,
        ]);

        $response->assertStatus(200)->assertJson(['status' => 1]);
        $this->assertSame(1, WorkLog::count());

        $log = WorkLog::first();
        $this->assertSame($this->patient->id, $log->patient_id);
        $this->assertNotNull($log->invoice_id);
    }

    public function test_store_endpoint_rejects_empty_rows(): void
    {
        $response = $this->actingAs($this->admin)->postJson('work-log-ocr/store', [
            'rows' => [],
        ]);

        $response->assertStatus(200)->assertJson(['status' => 0]);
        $this->assertSame(0, WorkLog::count());
    }

    public function test_oversized_upload_returns_actionable_message_not_generic_uploaded_error(): void
    {
        // Simulate PHP rejecting the file because it exceeds upload_max_filesize
        // (UPLOAD_ERR_INI_SIZE). $test=false so isValid() reflects the error.
        $file = new \Illuminate\Http\UploadedFile(
            __FILE__,                 // path is not checked when error !== OK
            'worklog.jpg',
            'image/jpeg',
            UPLOAD_ERR_INI_SIZE,
            false
        );

        $response = $this->actingAs($this->admin)
            ->post('work-log-ocr/recognize', ['image' => $file]);

        $response->assertStatus(200)->assertJson(['status' => 0]);

        $message = $response->json('message');
        // Must NOT be the cryptic default uploaded-rule message.
        $this->assertNotSame('image 上传失败。', $message);
        // Must state the actual limit and give the operator a useful next step,
        // without exposing PHP configuration names in the UI.
        $this->assertStringContainsString((string) ini_get('upload_max_filesize'), $message);
        $this->assertStringContainsString('压缩', $message);
        $this->assertStringContainsString('联系管理员', $message);
        $this->assertStringNotContainsString('upload_max_filesize', $message);
    }
}
