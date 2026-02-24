<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCockpitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nurse;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $nurseRole = Role::create(['name' => 'Nurse', 'slug' => 'nurse']);

        $viewReports = Permission::create([
            'name'   => 'View Reports',
            'slug'   => 'view-reports',
            'module' => 'reports',
        ]);

        RolePermission::create([
            'role_id'       => $adminRole->id,
            'permission_id' => $viewReports->id,
        ]);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
        ]);

        $this->nurse = User::factory()->create([
            'role_id'   => $nurseRole->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_cockpit_page_loads(): void
    {
        $response = $this->actingAs($this->admin)->get('/business-cockpit');

        $response->assertOk();
        $response->assertViewIs('reports.business_cockpit');
    }

    public function test_cockpit_contains_kpi_data(): void
    {
        $response = $this->actingAs($this->admin)->get('/business-cockpit');

        $response->assertOk();
        $response->assertViewHas('kpi');
        $response->assertViewHas('revenueTrend');
        $response->assertViewHas('paymentMix');
        $response->assertViewHas('completionTrend');
        $response->assertViewHas('doctorRanking');
        $response->assertViewHas('topServices');
        $response->assertViewHas('pendingItems');
    }

    public function test_cockpit_unauthorized_for_nurse(): void
    {
        $response = $this->actingAs($this->nurse)->get('/business-cockpit');

        $response->assertStatus(403);
    }
}
