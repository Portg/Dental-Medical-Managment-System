<?php

namespace Tests\Feature;

use App\Branch;
use App\MemberAuditLog;
use App\MemberLevel;
use App\MemberSetting;
use App\MemberSharedHolder;
use App\MemberTransaction;
use App\Patient;
use App\Role;
use App\Services\MemberService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MemberEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private MemberLevel $silver;
    private MemberLevel $gold;
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

        $this->silver = MemberLevel::create([
            'name'            => '银卡',
            'code'            => 'SILVER',
            'discount_rate'   => 5,
            'points_rate'     => 1,
            'min_consumption' => 0,
            'is_active'       => true,
            'sort_order'      => 1,
            'opening_fee'     => 0,
            'min_initial_deposit' => 0,
            'deposit_bonus_rules' => [
                ['min_amount' => 500,  'bonus' => 30],
                ['min_amount' => 1000, 'bonus' => 100],
                ['min_amount' => 5000, 'bonus' => 800],
            ],
            'referral_points' => 50,
            '_who_added'      => $this->admin->id,
        ]);

        $this->gold = MemberLevel::create([
            'name'            => '金卡',
            'code'            => 'GOLD',
            'discount_rate'   => 10,
            'points_rate'     => 2,
            'min_consumption' => 10000,
            'is_active'       => true,
            'sort_order'      => 2,
            'opening_fee'     => 100,
            'min_initial_deposit' => 500,
            'deposit_bonus_rules' => [
                ['min_amount' => 1000, 'bonus' => 200],
                ['min_amount' => 5000, 'bonus' => 1000],
            ],
            'referral_points' => 100,
            'payment_method_points_rates' => [
                'Cash'    => 1.0,
                'WeChat'  => 1.5,
                'Alipay'  => 1.5,
                'StoredValue' => 0,
            ],
            '_who_added'      => $this->admin->id,
        ]);

        $this->memberService = new MemberService();
    }

    // ─── Deposit bonus ──────────────────────────────────────────────

    public function test_deposit_bonus_calculation(): void
    {
        // Register with silver level
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        // Deposit 1000 → should get 100 bonus
        $result = $this->memberService->deposit($this->patient->id, [
            'amount'         => 1000,
            'payment_method' => 'Cash',
        ]);

        $this->assertTrue($result['status']);
        $this->assertEquals(100, $result['bonus']);
        // Balance = 1000 + 100 bonus = 1100
        $this->assertEquals(1100, $this->patient->fresh()->member_balance);

        // Transaction should record bonus
        $tx = MemberTransaction::where('patient_id', $this->patient->id)
            ->where('transaction_type', 'Deposit')
            ->latest()
            ->first();
        $this->assertEquals(100, $tx->bonus_amount);
    }

    public function test_deposit_bonus_threshold(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        // Deposit 499 → below minimum tier, no bonus
        $result = $this->memberService->deposit($this->patient->id, [
            'amount'         => 499,
            'payment_method' => 'Cash',
        ]);

        $this->assertTrue($result['status']);
        $this->assertEquals(0, $result['bonus']);
        $this->assertEquals(499, $this->patient->fresh()->member_balance);
    }

    // ─── Opening fee ────────────────────────────────────────────────

    public function test_opening_fee_deducted(): void
    {
        // Gold level has opening_fee = 100
        $result = $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->gold->id,
            'initial_balance' => 1000,
            'payment_method'  => 'Cash',
        ]);

        $this->assertTrue($result['status']);

        // Balance = 1000 - 100 (opening fee) + 200 (bonus for 1000) = 1100
        $this->assertEquals(1100, $this->patient->fresh()->member_balance);
    }

    // ─── Min initial deposit ────────────────────────────────────────

    public function test_min_initial_deposit_enforced(): void
    {
        // Gold level min_initial_deposit = 500
        $result = $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->gold->id,
            'initial_balance' => 100,
            'payment_method'  => 'Cash',
        ]);

        $this->assertFalse($result['status']);
        // Patient should NOT be registered
        $this->assertNull($this->patient->fresh()->member_no);
    }

    // ─── Points exchange ────────────────────────────────────────────

    public function test_points_exchange(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        // Manually set some points
        $this->patient->update(['member_points' => 500]);

        // Exchange 100 points → 1 unit balance (default rate: 100 points = 1)
        $result = $this->memberService->exchangePoints($this->patient->id, 100);

        $this->assertTrue($result['status']);

        $fresh = $this->patient->fresh();
        $this->assertEquals(400, $fresh->member_points);
        $this->assertEquals(1.0, $fresh->member_balance);
    }

    public function test_points_exchange_insufficient(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        $this->patient->update(['member_points' => 50]);

        $result = $this->memberService->exchangePoints($this->patient->id, 100);

        $this->assertFalse($result['status']);
        // Points should be unchanged
        $this->assertEquals(50, $this->patient->fresh()->member_points);
    }

    // ─── Auto upgrade ───────────────────────────────────────────────

    public function test_auto_upgrade_on_consumption(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        // Set consumption to reach gold level threshold
        $this->patient->update(['total_consumption' => 10000]);
        $this->patient->refresh();

        $upgraded = $this->memberService->checkAndUpgrade($this->patient);

        $this->assertTrue($upgraded);
        $this->assertEquals($this->gold->id, $this->patient->fresh()->member_level_id);
    }

    public function test_auto_upgrade_skip_if_already_highest(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->gold->id,
            'initial_balance' => 500,
            'payment_method'  => 'Cash',
        ]);

        $this->patient->update(['total_consumption' => 50000]);
        $this->patient->refresh();

        // Already at gold (highest), should not upgrade
        $upgraded = $this->memberService->checkAndUpgrade($this->patient);

        $this->assertFalse($upgraded);
    }

    // ─── Shared card holders ────────────────────────────────────────

    public function test_shared_holder_add_remove(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        $shared = Patient::create([
            'patient_no' => '20260002',
            'surname'    => '李',
            'othername'  => '四',
            'gender'     => 'Female',
            'phone_no'   => '13900139000',
            '_who_added' => $this->admin->id,
        ]);

        // Add shared holder
        $result = $this->memberService->addSharedHolder($this->patient->id, $shared->id, 'spouse');
        $this->assertTrue($result['status']);

        $this->assertDatabaseHas('member_shared_holders', [
            'primary_patient_id' => $this->patient->id,
            'shared_patient_id'  => $shared->id,
            'relationship'       => 'spouse',
        ]);

        // Remove
        $holder = MemberSharedHolder::where('primary_patient_id', $this->patient->id)->first();
        $result = $this->memberService->removeSharedHolder($holder->id);
        $this->assertTrue($result['status']);

        // Soft deleted
        $this->assertSoftDeleted('member_shared_holders', ['id' => $holder->id]);
    }

    public function test_shared_holder_cannot_add_self(): void
    {
        $result = $this->memberService->addSharedHolder($this->patient->id, $this->patient->id, 'other');
        $this->assertFalse($result['status']);
    }

    public function test_shared_holder_resolve_primary(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
            'initial_balance' => 5000,
            'payment_method'  => 'Cash',
        ]);

        $shared = Patient::create([
            'patient_no' => '20260003',
            'surname'    => '王',
            'othername'  => '五',
            'gender'     => 'Male',
            '_who_added' => $this->admin->id,
        ]);

        $this->memberService->addSharedHolder($this->patient->id, $shared->id, 'child');

        // Resolve primary member for the shared patient
        $primary = $this->memberService->resolvePrimaryMember($shared->id);
        $this->assertEquals($this->patient->id, $primary->id);

        // Resolve for the primary themselves
        $self = $this->memberService->resolvePrimaryMember($this->patient->id);
        $this->assertEquals($this->patient->id, $self->id);
    }

    // ─── Referral points ────────────────────────────────────────────

    public function test_referral_points_awarded(): void
    {
        // Register the referrer first
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        // Enable referral bonus
        MemberSetting::set('referral_bonus_enabled', '1');

        $newPatient = Patient::create([
            'patient_no' => '20260004',
            'surname'    => '赵',
            'othername'  => '六',
            'gender'     => 'Female',
            'phone_no'   => '13700137000',
            '_who_added' => $this->admin->id,
        ]);

        // Register new patient with referrer
        $result = $this->memberService->registerMember([
            'patient_id'      => $newPatient->id,
            'member_level_id' => $this->silver->id,
            'referred_by'     => $this->patient->id,
        ]);

        $this->assertTrue($result['status']);

        // Referrer should get 50 points (silver level referral_points)
        $this->assertEquals(50, $this->patient->fresh()->member_points);
    }

    // ─── Payment method points rates ────────────────────────────────

    public function test_payment_method_points_rates(): void
    {
        // Gold level has custom rates: WeChat = 1.5, Cash = 1.0
        $rate = $this->gold->getPointsRateForMethod('WeChat');
        $this->assertEquals(1.5, $rate);

        $rate = $this->gold->getPointsRateForMethod('Cash');
        $this->assertEquals(1.0, $rate);

        // StoredValue = 0 (no points)
        $rate = $this->gold->getPointsRateForMethod('StoredValue');
        $this->assertEquals(0, $rate);

        // Unknown method falls back to default points_rate (2)
        $rate = $this->gold->getPointsRateForMethod('Unknown');
        $this->assertEquals(2.0, $rate);
    }

    // ─── Card number modes ──────────────────────────────────────────

    public function test_card_number_phone_mode(): void
    {
        MemberSetting::set('card_number_mode', 'phone');

        $memberNo = Patient::generateMemberNo($this->patient);
        $this->assertEquals('13800138000', $memberNo);
    }

    public function test_card_number_manual_mode(): void
    {
        MemberSetting::set('card_number_mode', 'manual');

        $memberNo = Patient::generateMemberNo($this->patient, 'VIP-001');
        $this->assertEquals('VIP-001', $memberNo);
    }

    public function test_card_number_auto_mode(): void
    {
        MemberSetting::set('card_number_mode', 'auto');

        $memberNo = Patient::generateMemberNo();
        $this->assertStringStartsWith('M' . date('Y'), $memberNo);
    }

    // ─── Member password ────────────────────────────────────────────

    public function test_member_password_set_verify(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        $result = $this->memberService->setPassword($this->patient->id, '1234');
        $this->assertTrue($result['status']);

        // Verify correct password
        $this->assertTrue($this->memberService->verifyPassword($this->patient->id, '1234'));

        // Verify wrong password
        $this->assertFalse($this->memberService->verifyPassword($this->patient->id, '0000'));
    }

    // ─── Audit log ──────────────────────────────────────────────────

    public function test_audit_log_created_on_changes(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        // Registration should create an audit log
        $logs = MemberAuditLog::where('patient_id', $this->patient->id)->get();
        $this->assertTrue($logs->count() > 0);
        $this->assertEquals('register', $logs->first()->action);
    }

    // ─── Points disabled globally ───────────────────────────────────

    public function test_points_disabled_prevents_exchange(): void
    {
        $this->memberService->registerMember([
            'patient_id'      => $this->patient->id,
            'member_level_id' => $this->silver->id,
        ]);

        $this->patient->update(['member_points' => 500]);

        MemberSetting::set('points_enabled', '0');

        $result = $this->memberService->exchangePoints($this->patient->id, 100);
        $this->assertFalse($result['status']);

        // Points unchanged
        $this->assertEquals(500, $this->patient->fresh()->member_points);
    }

    // ─── MemberLevel calculateBonus ─────────────────────────────────

    public function test_calculate_bonus_highest_tier(): void
    {
        // Silver: 500→30, 1000→100, 5000→800
        $this->assertEquals(800, $this->silver->calculateBonus(5000));
        $this->assertEquals(800, $this->silver->calculateBonus(6000));
    }

    public function test_calculate_bonus_middle_tier(): void
    {
        $this->assertEquals(100, $this->silver->calculateBonus(1000));
        $this->assertEquals(100, $this->silver->calculateBonus(2000));
    }

    public function test_calculate_bonus_lowest_tier(): void
    {
        $this->assertEquals(30, $this->silver->calculateBonus(500));
        $this->assertEquals(30, $this->silver->calculateBonus(999));
    }

    public function test_calculate_bonus_below_all_tiers(): void
    {
        $this->assertEquals(0, $this->silver->calculateBonus(100));
        $this->assertEquals(0, $this->silver->calculateBonus(0));
    }

    // ─── MemberSetting ─────────────────────────────────────────────

    public function test_member_setting_get_set(): void
    {
        MemberSetting::set('test_key', 'test_value');
        $this->assertEquals('test_value', MemberSetting::get('test_key'));
    }

    public function test_member_setting_default(): void
    {
        $this->assertEquals('fallback', MemberSetting::get('nonexistent_key', 'fallback'));
    }
}
