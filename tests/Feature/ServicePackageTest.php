<?php

namespace Tests\Feature;

use App\Branch;
use App\MedicalService;
use App\Permission;
use App\Role;
use App\ServicePackage;
use App\ServicePackageItem;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePackageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $perm = Permission::create([
            'name'        => '管理收费套餐',
            'slug'        => 'manage-service-packages',
            'module'      => '医疗管理',
            'description' => '管理收费套餐与明细',
        ]);
        $adminRole->permissions()->syncWithoutDetaching([$perm->id]);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
            'status'    => 'active',
        ]);
    }

    private function makeService(string $name, float $price = 100.0): MedicalService
    {
        return MedicalService::create([
            'name'       => $name,
            'price'      => $price,
            '_who_added' => $this->admin->id,
        ]);
    }

    /** @test */
    public function it_can_create_a_package_with_items(): void
    {
        $svc1 = $this->makeService('洁牙', 200.0);
        $svc2 = $this->makeService('抛光', 50.0);

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/service-packages', [
                'name'        => '洁牙套餐',
                'total_price' => 220.0,
                'items'       => [
                    ['service_id' => $svc1->id, 'qty' => 1, 'price' => 180.0],
                    ['service_id' => $svc2->id, 'qty' => 1, 'price' => 40.0],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseHas('service_packages', ['name' => '洁牙套餐']);

        $pkg = ServicePackage::where('name', '洁牙套餐')->first();
        $this->assertNotNull($pkg);
        $this->assertCount(2, $pkg->items);
    }

    /** @test */
    public function it_replaces_items_on_update(): void
    {
        $svc1 = $this->makeService('洁牙', 200.0);
        $svc2 = $this->makeService('美白', 300.0);

        $pkg = ServicePackage::create([
            'name'        => '洁牙套餐',
            'total_price' => 200.0,
            'is_active'   => true,
            '_who_added'  => $this->admin->id,
        ]);
        $pkg->items()->create([
            'service_id' => $svc1->id,
            'qty'        => 1,
            'price'      => 200.0,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/admin/service-packages/{$pkg->id}", [
                'name'        => '洁牙美白套餐',
                'total_price' => 450.0,
                'items'       => [
                    ['service_id' => $svc1->id, 'qty' => 1, 'price' => 180.0],
                    ['service_id' => $svc2->id, 'qty' => 1, 'price' => 270.0],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseHas('service_packages', ['id' => $pkg->id, 'name' => '洁牙美白套餐']);

        $pkg->refresh();
        $this->assertCount(2, $pkg->items);
        $this->assertEquals(450.0, (float) $pkg->total_price);
    }

    /** @test */
    public function it_can_soft_delete_a_package(): void
    {
        $pkg = ServicePackage::create([
            'name'        => '临时套餐',
            'total_price' => 100.0,
            'is_active'   => true,
            '_who_added'  => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/admin/service-packages/{$pkg->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertSoftDeleted('service_packages', ['id' => $pkg->id]);
    }
}
