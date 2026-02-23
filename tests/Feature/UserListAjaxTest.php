<?php

namespace Tests\Feature;

use App\Branch;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListAjaxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'surname'   => 'Admin',
            'othername' => 'Test',
        ]);
    }

    /**
     * Reproduce: DataTables sends search[value] and search[regex] as array.
     * UserService::getUserList() must handle this without TypeError.
     */
    public function test_users_datatable_ajax_returns_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/users?' . http_build_query([
                'draw'    => 1,
                'start'   => 0,
                'length'  => 10,
                'search'  => ['value' => '', 'regex' => 'false'],
                'columns' => [],
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200)
                 ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /**
     * Ensure search with a non-empty value also works.
     */
    public function test_users_datatable_search_filter_works(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/users?' . http_build_query([
                'draw'    => 1,
                'start'   => 0,
                'length'  => 10,
                'search'  => ['value' => 'Admin', 'regex' => 'false'],
                'columns' => [],
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200)
                 ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }
}
