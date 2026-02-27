<?php

namespace Tests\Feature\Api;

use App\Branch;
use App\MemberLevel;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MemberApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private MemberLevel $level;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
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

        $this->level = MemberLevel::create([
            'name'          => '银卡',
            'code'          => 'SILVER',
            'discount_rate' => 5,
            'points_rate'   => 1,
            'is_active'     => true,
            '_who_added'    => $this->admin->id,
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ═══════════════════════════════════════════════════════════════
    //  Members (patients as members)
    // ═══════════════════════════════════════════════════════════════

    // ─── Register ───────────────────────────────────────────────────

    public function test_register_member(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', [
                'patient_id'      => $this->patient->id,
                'member_level_id' => $this->level->id,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('patients', [
            'id'              => $this->patient->id,
            'member_level_id' => $this->level->id,
            'member_status'   => 'Active',
        ]);
    }

    public function test_register_member_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── List ───────────────────────────────────────────────────────

    public function test_list_members(): void
    {
        // Register first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', [
                'patient_id'      => $this->patient->id,
                'member_level_id' => $this->level->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/members');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['total']]);
    }

    // ─── Show ───────────────────────────────────────────────────────

    public function test_show_member(): void
    {
        // Register first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', [
                'patient_id'      => $this->patient->id,
                'member_level_id' => $this->level->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/members/{$this->patient->id}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertNotNull($response->json('data.member_no'));
    }

    // ─── Update ─────────────────────────────────────────────────────

    public function test_update_member(): void
    {
        // Register first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', [
                'patient_id'      => $this->patient->id,
                'member_level_id' => $this->level->id,
            ]);

        // Create a second level
        $goldLevel = MemberLevel::create([
            'name'          => '金卡',
            'code'          => 'GOLD',
            'discount_rate' => 10,
            'points_rate'   => 2,
            'is_active'     => true,
            '_who_added'    => $this->admin->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/members/{$this->patient->id}", [
                'member_level_id' => $goldLevel->id,
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('patients', [
            'id'              => $this->patient->id,
            'member_level_id' => $goldLevel->id,
        ]);
    }

    // ─── Deposit ────────────────────────────────────────────────────

    public function test_deposit_to_member(): void
    {
        // Register first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', [
                'patient_id'      => $this->patient->id,
                'member_level_id' => $this->level->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/members/{$this->patient->id}/deposit", [
                'amount'         => 1000,
                'payment_method' => 'Cash',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.new_balance', 1000);
    }

    // ─── Transactions ───────────────────────────────────────────────

    public function test_member_transactions(): void
    {
        // Register first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', [
                'patient_id'      => $this->patient->id,
                'member_level_id' => $this->level->id,
            ]);

        // Deposit
        $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/members/{$this->patient->id}/deposit", [
                'amount'         => 1000,
                'payment_method' => 'Cash',
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/members/{$this->patient->id}/transactions");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Auth guard ─────────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/members')->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Member Levels
    // ═══════════════════════════════════════════════════════════════

    // ─── List ───────────────────────────────────────────────────────

    public function test_list_member_levels(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/member-levels');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Create ─────────────────────────────────────────────────────

    public function test_create_member_level(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/member-levels', [
                'name'          => '金卡',
                'code'          => 'GOLD',
                'discount_rate' => 10,
                'points_rate'   => 2,
                'is_active'     => true,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('member_levels', [
            'name' => '金卡',
            'code' => 'GOLD',
        ]);
    }

    // ─── Show ───────────────────────────────────────────────────────

    public function test_show_member_level(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/member-levels/{$this->level->id}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Update ─────────────────────────────────────────────────────

    public function test_update_member_level(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/member-levels/{$this->level->id}", [
                'name'          => '白银卡',
                'code'          => 'SILVER',
                'discount_rate' => 8,
                'points_rate'   => 1.5,
                'is_active'     => true,
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('member_levels', [
            'id'            => $this->level->id,
            'name'          => '白银卡',
            'discount_rate' => 8,
        ]);
    }

    // ─── Delete ─────────────────────────────────────────────────────

    public function test_delete_member_level(): void
    {
        // Create a new level without any members
        $tempLevel = MemberLevel::create([
            'name'          => '临时卡',
            'code'          => 'TEMP',
            'discount_rate' => 1,
            'points_rate'   => 1,
            'is_active'     => true,
            '_who_added'    => $this->admin->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/member-levels/{$tempLevel->id}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    public function test_delete_member_level_with_members_fails(): void
    {
        // Register a member to this level
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/members/register', [
                'patient_id'      => $this->patient->id,
                'member_level_id' => $this->level->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/member-levels/{$this->level->id}");

        $response->assertJsonPath('success', false);
    }
}
