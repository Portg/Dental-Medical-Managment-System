<?php

namespace Tests\Feature;

use App\Branch;
use App\MemberLevel;
use App\MemberTransaction;
use App\Patient;
use App\Role;
use App\Services\MemberService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MemberServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private MemberLevel $level;
    private MemberService $memberService;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        Auth::login($this->admin);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $this->level = MemberLevel::create([
            'name'          => '银卡',
            'code'          => 'SILVER',
            'discount_rate' => 5,
            'points_rate'   => 1,
            'is_active'     => true,
            '_who_added'    => $this->admin->id,
        ]);

        $this->memberService = new MemberService();
    }

    // ─── Register member ─────────────────────────────────────────

    public function test_register_member_success(): void
    {
        $result = $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->level->id,
        ]);

        $this->assertTrue($result['status']);

        $fresh = $this->patient->fresh();
        $this->assertNotNull($fresh->member_no);
        $this->assertEquals('Active', $fresh->member_status);
        $this->assertEquals($this->level->id, $fresh->member_level_id);
    }

    public function test_register_member_with_initial_deposit(): void
    {
        $result = $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->level->id,
            'initial_balance' => 5000,
            'payment_method'  => 'Cash',
        ]);

        $this->assertTrue($result['status']);
        $this->assertEquals(5000, $this->patient->fresh()->member_balance);

        // Should have a deposit transaction
        $this->assertDatabaseHas('member_transactions', [
            'patient_id'       => $this->patient->id,
            'transaction_type' => 'Deposit',
            'amount'           => 5000,
        ]);
    }

    public function test_register_already_active_fails(): void
    {
        // Register first time
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->level->id,
        ]);

        // Try again
        $result = $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->level->id,
        ]);

        $this->assertFalse($result['status']);
    }

    // ─── Deposit ─────────────────────────────────────────────────

    public function test_deposit_updates_balance(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->level->id,
        ]);

        $result = $this->memberService->deposit($this->patient->id, [
            'amount'         => 3000,
            'payment_method' => 'Cash',
        ]);

        $this->assertTrue($result['status']);
        $this->assertEquals(3000, $result['new_balance']);
        $this->assertEquals(3000, $this->patient->fresh()->member_balance);

        $this->assertDatabaseHas('member_transactions', [
            'patient_id'       => $this->patient->id,
            'transaction_type' => 'Deposit',
            'amount'           => 3000,
            'balance_before'   => 0,
            'balance_after'    => 3000,
        ]);
    }

    public function test_deposit_inactive_fails(): void
    {
        // Patient is not a member, member_status is null
        $result = $this->memberService->deposit($this->patient->id, [
            'amount'         => 1000,
            'payment_method' => 'Cash',
        ]);

        $this->assertFalse($result['status']);
    }

    // ─── Delete level ────────────────────────────────────────────

    public function test_delete_level_with_members_fails(): void
    {
        // Register patient to this level
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->level->id,
        ]);

        $result = $this->memberService->deleteLevel($this->level->id);

        $this->assertFalse($result['status']);
    }
}
