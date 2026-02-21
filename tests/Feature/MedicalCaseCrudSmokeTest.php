<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MedicalCaseCrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch    = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole = Role::create(['name' => 'Administrator']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-medical-cases'],
            ['name' => 'Manage Medical Cases']
        );
        RolePermission::firstOrCreate([
            'role_id'       => $adminRole->id,
            'permission_id' => $permission->id,
        ]);
    }

    /** @test */
    public function medical_cases_datatable_returns_success(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/medical-cases?draw=1&start=0&length=10', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }
}
