<?php

namespace Tests\Feature\Api;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RefundApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private Appointment $appointment;
    private Invoice $invoice;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch     = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole  = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $this->appointment = Appointment::create([
            'start_date'        => now()->addDay()->format('Y-m-d'),
            'end_date'          => now()->addDay()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information'  => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->addDay()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'         => 'INV20260001',
            'appointment_id'     => $this->appointment->id,
            'patient_id'         => $this->patient->id,
            'total_amount'       => 500,
            'paid_amount'        => 500,
            'outstanding_amount' => 0,
            '_who_added'         => $this->admin->id,
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_refunds(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/refunds');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Create (auto-approved, <= 100 threshold) ──────────────────

    public function test_create_refund(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/refunds', [
                'invoice_id'    => $this->invoice->id,
                'refund_amount' => 50,
                'refund_reason' => '退费测试',
                'refund_method' => 'Cash',
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.needs_approval', false);
    }

    public function test_create_refund_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/refunds', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_refund(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/refunds', [
                'invoice_id'    => $this->invoice->id,
                'refund_amount' => 50,
                'refund_reason' => '退费测试',
                'refund_method' => 'Cash',
            ]);

        $refundId = $create->json('data.refund_id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/refunds/{$refundId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Delete (pending only) ─────────────────────────────────────

    public function test_delete_pending_refund(): void
    {
        // Create a refund > 100 so it stays pending
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/refunds', [
                'invoice_id'    => $this->invoice->id,
                'refund_amount' => 200,
                'refund_reason' => '大额退费测试',
                'refund_method' => 'Cash',
            ]);

        $refundId = $create->json('data.refund_id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/refunds/{$refundId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Approve ───────────────────────────────────────────────────

    public function test_approve_refund(): void
    {
        // Create a refund > 100 so it stays pending
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/refunds', [
                'invoice_id'    => $this->invoice->id,
                'refund_amount' => 200,
                'refund_reason' => '等待审批',
                'refund_method' => 'Cash',
            ]);

        $refundId = $create->json('data.refund_id');

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/refunds/{$refundId}/approve");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Reject ────────────────────────────────────────────────────

    public function test_reject_refund(): void
    {
        // Create a refund > 100 so it stays pending
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/refunds', [
                'invoice_id'    => $this->invoice->id,
                'refund_amount' => 200,
                'refund_reason' => '等待审批',
                'refund_method' => 'Cash',
            ]);

        $refundId = $create->json('data.refund_id');

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/refunds/{$refundId}/reject", [
                'rejection_reason' => '不符合退费条件',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Pending approvals ─────────────────────────────────────────

    public function test_pending_approvals(): void
    {
        // Create a refund > 100 so it stays pending
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/refunds', [
                'invoice_id'    => $this->invoice->id,
                'refund_amount' => 200,
                'refund_reason' => '等待审批',
                'refund_method' => 'Cash',
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/refunds/pending');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }
}
