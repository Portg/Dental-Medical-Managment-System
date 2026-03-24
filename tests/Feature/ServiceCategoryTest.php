<?php

namespace Tests\Feature;

use App\Branch;
use App\Permission;
use App\Role;
use App\ServiceCategory;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $perm = Permission::create([
            'name'        => '管理收费大类',
            'slug'        => 'manage-service-categories',
            'module'      => '医疗管理',
            'description' => '管理收费项目大类分类',
        ]);
        $adminRole->permissions()->syncWithoutDetaching([$perm->id]);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
            'status'    => 'active',
        ]);
    }

    /** @test */
    public function it_can_list_service_categories(): void
    {
        ServiceCategory::create(['name' => '正畸', 'sort_order' => 1, 'is_active' => true]);
        ServiceCategory::create(['name' => '种植', 'sort_order' => 2, 'is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->getJson('/admin/service-categories');

        $response->assertStatus(200)
            ->assertJson(['status' => 1])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_create_a_service_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/service-categories', ['name' => '洁牙']);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseHas('service_categories', ['name' => '洁牙']);
    }

    /** @test */
    public function it_fails_on_duplicate_name(): void
    {
        ServiceCategory::create(['name' => '正畸', 'sort_order' => 1, 'is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/service-categories', ['name' => '正畸']);

        $response->assertStatus(200)
            ->assertJson(['status' => 0]);
    }

    /** @test */
    public function it_can_reorder_service_categories(): void
    {
        $a = ServiceCategory::create(['name' => '正畸', 'sort_order' => 1, 'is_active' => true]);
        $b = ServiceCategory::create(['name' => '种植', 'sort_order' => 2, 'is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/service-categories/reorder', [
                'order' => [$b->id, $a->id],
            ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseHas('service_categories', ['id' => $b->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('service_categories', ['id' => $a->id, 'sort_order' => 2]);
    }

    /** @test */
    public function it_can_update_a_service_category(): void
    {
        $category = ServiceCategory::create(['name' => '正畸', 'sort_order' => 1, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->putJson("/admin/service-categories/{$category->id}", [
                'name'       => '正畸修改',
                'sort_order' => 2,
                'is_active'  => true,
            ])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $this->assertEquals('正畸修改', ServiceCategory::find($category->id)->name);
    }

    /** @test */
    public function it_can_delete_a_service_category(): void
    {
        $category = ServiceCategory::create(['name' => '种植', 'sort_order' => 1, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->deleteJson("/admin/service-categories/{$category->id}")
            ->assertOk()
            ->assertJson(['status' => 1]);

        $this->assertSoftDeleted('service_categories', ['id' => $category->id]);
    }
}
