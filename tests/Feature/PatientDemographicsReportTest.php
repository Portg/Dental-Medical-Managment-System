<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientDemographicsReportTest extends TestCase
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
    public function patient_demographics_report_loads_without_sql_error(): void
    {
        // Create patients with and without date_of_birth to exercise all code paths
        $base = ['surname' => 'Test', 'gender' => 'male', '_who_added' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()];
        DB::table('patients')->insert(array_merge($base, ['surname' => 'Adult', 'date_of_birth' => '1990-05-15']));
        DB::table('patients')->insert(array_merge($base, ['surname' => 'Child', 'date_of_birth' => '2010-03-20']));
        DB::table('patients')->insert(array_merge($base, ['surname' => 'NoDob', 'date_of_birth' => null]));

        $response = $this->actingAs($this->admin)
            ->get('/patient-demographics-report');

        $response->assertOk();
        $response->assertViewHas('totalPatients');
        $response->assertViewHas('ageDistribution');
        $response->assertViewHas('genderDistribution');
    }

    /** @test */
    public function patient_demographics_report_works_with_no_patients(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/patient-demographics-report');

        $response->assertOk();
        $response->assertViewHas('totalPatients', 0);
    }
}
