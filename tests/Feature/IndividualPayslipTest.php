<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndividualPayslipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $branch    = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $perm = Permission::firstOrCreate(['slug' => 'manage-payroll', 'name' => 'Manage Payroll']);
        RolePermission::create(['role_id' => $adminRole->id, 'permission_id' => $perm->id]);
    }

    /** @test */
    public function individual_payslips_page_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/individual-payslips');

        $response->assertOk();
    }

    /** @test */
    public function individual_payslips_ajax_returns_valid_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/individual-payslips', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }
}
