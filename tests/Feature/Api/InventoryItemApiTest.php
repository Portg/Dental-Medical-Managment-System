<?php

namespace Tests\Feature\Api;

use App\Branch;
use App\InventoryCategory;
use App\InventoryItem;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private InventoryCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;

        $this->category = InventoryCategory::create([
            'name'       => '耗材',
            'code'       => 'CONSUMABLE',
            'is_active'  => true,
            '_who_added' => $this->admin->id,
        ]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validItemData(array $overrides = []): array
    {
        return array_merge([
            'item_code'           => 'ITM-001',
            'name'                => '一次性手套',
            'unit'                => '盒',
            'category_id'         => $this->category->id,
            'reference_price'     => 50.00,
            'selling_price'       => 80.00,
            'stock_warning_level' => 10,
        ], $overrides);
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_inventory_items(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/inventory-items', $this->validItemData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/inventory-items');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['total']]);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_inventory_item(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/inventory-items', $this->validItemData());

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.item_code', 'ITM-001')
                 ->assertJsonPath('data.name', '一次性手套');

        $this->assertDatabaseHas('inventory_items', [
            'item_code' => 'ITM-001',
            'name'      => '一次性手套',
        ]);
    }

    public function test_create_inventory_item_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/inventory-items', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_inventory_item(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/inventory-items', $this->validItemData());

        $itemId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/inventory-items/{$itemId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $itemId)
                 ->assertJsonPath('data.name', '一次性手套');
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_inventory_item(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/inventory-items', $this->validItemData());

        $itemId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/inventory-items/{$itemId}", $this->validItemData([
                'name'          => '医用口罩',
                'selling_price' => 120.00,
            ]));

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.name', '医用口罩');

        $this->assertDatabaseHas('inventory_items', [
            'id'   => $itemId,
            'name' => '医用口罩',
        ]);
    }

    // ─── Delete (soft) ─────────────────────────────────────────────

    public function test_delete_inventory_item(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/inventory-items', $this->validItemData());

        $itemId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/inventory-items/{$itemId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('inventory_items', ['id' => $itemId]);
    }

    // ─── Search ────────────────────────────────────────────────────

    public function test_search_inventory_items(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/inventory-items', $this->validItemData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/inventory-items/search?q=手套');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Low stock ─────────────────────────────────────────────────

    public function test_low_stock_items(): void
    {
        InventoryItem::create([
            'item_code'           => 'ITM-LOW',
            'name'                => '低库存物品',
            'unit'                => '个',
            'category_id'         => $this->category->id,
            'reference_price'     => 10.00,
            'selling_price'       => 20.00,
            'current_stock'       => 2,
            'stock_warning_level' => 10,
            'is_active'           => true,
            '_who_added'          => $this->admin->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/inventory-items/low-stock');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Auth guard ────────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/inventory-items')->assertStatus(401);
        $this->postJson('/api/v1/inventory-items', $this->validItemData())->assertStatus(401);
    }
}
