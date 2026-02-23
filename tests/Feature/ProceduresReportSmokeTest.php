<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProceduresReportSmokeTest extends TestCase
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

        // Grant view-reports permission
        $perm = Permission::firstOrCreate(['slug' => 'view-reports', 'name' => 'View Reports']);
        RolePermission::create(['role_id' => $adminRole->id, 'permission_id' => $perm->id]);
    }

    /** @test */
    public function procedure_income_report_ajax_returns_json_with_dates(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/procedure-income-report?' . http_build_query([
                'draw'       => 1,
                'start'      => 0,
                'length'     => 10,
                'start_date' => now()->format('Y-m-d'),
                'end_date'   => now()->format('Y-m-d'),
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /** @test */
    public function procedure_income_report_ajax_returns_json_without_dates(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/procedure-income-report?' . http_build_query([
                'draw'   => 1,
                'start'  => 0,
                'length' => 10,
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }
}
