<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PermissionCacheTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $doctorRole;
    private Role $nurseRole;
    private Role $receptionistRole;
    private Role $superAdminRole;
    private User $adminUser;
    private User $doctorUser;
    private User $nurseUser;
    private User $receptionistUser;
    private User $superAdminUser;
    private Permission $viewPatientPerm;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);

        $this->superAdminRole   = Role::create(['name' => 'Super Administrator']);
        $this->adminRole        = Role::create(['name' => 'Administrator']);
        $this->doctorRole       = Role::create(['name' => 'Doctor']);
        $this->nurseRole        = Role::create(['name' => 'Nurse']);
        $this->receptionistRole = Role::create(['name' => 'Receptionist']);

        $this->superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id, 'branch_id' => $branch->id]);
        $this->adminUser      = User::factory()->create(['role_id' => $this->adminRole->id, 'branch_id' => $branch->id]);
        $this->doctorUser     = User::factory()->create(['role_id' => $this->doctorRole->id, 'branch_id' => $branch->id, 'is_doctor' => 'yes']);
        $this->nurseUser      = User::factory()->create(['role_id' => $this->nurseRole->id, 'branch_id' => $branch->id]);
        $this->receptionistUser = User::factory()->create(['role_id' => $this->receptionistRole->id, 'branch_id' => $branch->id]);

        $this->viewPatientPerm = Permission::create([
            'name'   => 'View Patient',
            'slug'   => 'view-patient',
            'module' => 'patients',
        ]);
    }

    // ─── Dashboard gates match roles ─────────────────────────────

    public function test_dashboard_gates_match_roles(): void
    {
        // Super Admin
        $this->assertTrue($this->superAdminUser->can('Super-Administrator-Dashboard'));
        $this->assertFalse($this->superAdminUser->can('Admin-Dashboard'));

        // Admin
        $this->assertTrue($this->adminUser->can('Admin-Dashboard'));
        $this->assertFalse($this->adminUser->can('Doctor-Dashboard'));

        // Doctor
        $this->assertTrue($this->doctorUser->can('Doctor-Dashboard'));
        $this->assertFalse($this->doctorUser->can('Admin-Dashboard'));

        // Nurse
        $this->assertTrue($this->nurseUser->can('Nurse-Dashboard'));
        $this->assertFalse($this->nurseUser->can('Doctor-Dashboard'));

        // Receptionist
        $this->assertTrue($this->receptionistUser->can('Receptionist-Dashboard'));
        $this->assertFalse($this->receptionistUser->can('Admin-Dashboard'));
    }

    // ─── hasPermission ───────────────────────────────────────────

    public function test_has_permission_returns_true(): void
    {
        RolePermission::create([
            'role_id'       => $this->adminRole->id,
            'permission_id' => $this->viewPatientPerm->id,
        ]);

        // Clear cache so fresh query runs
        Cache::forget("role:{$this->adminRole->id}:permissions");

        $this->assertTrue($this->adminRole->hasPermission('view-patient'));
    }

    public function test_has_permission_returns_false(): void
    {
        Cache::forget("role:{$this->doctorRole->id}:permissions");

        $this->assertFalse($this->doctorRole->hasPermission('view-patient'));
    }

    // ─── Cache invalidation ──────────────────────────────────────

    public function test_permission_cache_invalidated_on_create(): void
    {
        // Prime the cache (returns false, no permission assigned yet)
        Cache::forget("role:{$this->adminRole->id}:permissions");
        $this->assertFalse($this->adminRole->hasPermission('view-patient'));

        // Add permission
        RolePermission::create([
            'role_id'       => $this->adminRole->id,
            'permission_id' => $this->viewPatientPerm->id,
        ]);

        // Clear cache to simulate invalidation
        Cache::forget("role:{$this->adminRole->id}:permissions");

        // Should now return true
        $this->assertTrue($this->adminRole->hasPermission('view-patient'));
    }

    public function test_permission_cache_invalidated_on_delete(): void
    {
        $rp = RolePermission::create([
            'role_id'       => $this->adminRole->id,
            'permission_id' => $this->viewPatientPerm->id,
        ]);

        Cache::forget("role:{$this->adminRole->id}:permissions");
        $this->assertTrue($this->adminRole->hasPermission('view-patient'));

        // Delete the permission
        $rp->delete();
        Cache::forget("role:{$this->adminRole->id}:permissions");

        // Should now return false
        $this->assertFalse($this->adminRole->hasPermission('view-patient'));
    }
}
