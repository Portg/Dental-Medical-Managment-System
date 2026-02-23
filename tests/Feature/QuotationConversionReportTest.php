<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationConversionReportTest extends TestCase
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

        $perm = Permission::firstOrCreate(['slug' => 'view-reports', 'name' => 'View Reports']);
        RolePermission::create(['role_id' => $adminRole->id, 'permission_id' => $perm->id]);
    }

    /** @test */
    public function quotation_conversion_report_loads_without_sql_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/quotation-conversion-report');

        $response->assertOk();
        $response->assertViewHas('summary');
        $response->assertViewHas('byDoctor');
        $response->assertViewHas('monthlyTrend');
        $response->assertViewHas('unconvertedList');
    }

    /** @test */
    public function quotation_conversion_report_accepts_date_range(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/quotation-conversion-report?start_date=2026-01-01&end_date=2026-02-28');

        $response->assertOk();
        $response->assertViewHas('summary');
    }
}
