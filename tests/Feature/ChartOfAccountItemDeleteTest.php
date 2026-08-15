<?php

namespace Tests\Feature;

use App\AccountingEquation;
use App\Branch;
use App\ChartOfAccountCategory;
use App\ChartOfAccountItem;
use App\ExpenseCategory;
use App\Permission;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 会计科目表的删除按钮此前是死的。
 *
 * charts_of_accounts_index.js 的 deleteRecord() 一直在发 DELETE
 * /charts-of-accounts-items/{id}，而控制器的 destroy() 方法体是 `//`：
 * 返回空 200，前端读不到 data.status 就走 else 分支，弹一个没有文字的红框，
 * 1.9 秒后刷新——记录还在，用户以为是自己点错了。
 *
 * 顺带两个约束一起锁住：
 *   - 模型没挂 SoftDeletes 时 ->delete() 是硬删，而两张表有外键指过来，必炸；
 *   - 挂上 SoftDeletes 后，ChartOfAccountCategory::Items() 才会自动过滤掉已删的科目。
 */
class ChartOfAccountItemDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ChartOfAccountCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $perm = Permission::create([
            'name'        => '管理会计',
            'slug'        => 'manage-accounting',
            'module'      => '财务管理',
            'description' => '管理会计科目与账务设置',
        ]);
        $adminRole->permissions()->syncWithoutDetaching([$perm->id]);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
            'status'    => 'active',
        ]);

        $equation = AccountingEquation::create([
            'name'       => '资产',
            'sort_by'    => 1,
            'active_tab' => true,
            '_who_added' => $this->admin->id,
        ]);

        $this->category = ChartOfAccountCategory::create([
            'name'                   => '流动资产',
            'accounting_equation_id' => $equation->id,
            '_who_added'             => $this->admin->id,
        ]);
    }

    private function makeItem(string $name = '库存现金'): ChartOfAccountItem
    {
        return ChartOfAccountItem::create([
            'name'                         => $name,
            'chart_of_account_category_id' => $this->category->id,
            '_who_added'                   => $this->admin->id,
        ]);
    }

    /** @test */
    public function 删除未被引用的科目会真的落库(): void
    {
        $item = $this->makeItem();

        $response = $this->actingAs($this->admin)
            ->deleteJson('/charts-of-accounts-items/' . $item->id);

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertNull(ChartOfAccountItem::find($item->id));
    }

    /** @test */
    public function 删除走的是软删而不是硬删(): void
    {
        $item = $this->makeItem();

        $this->actingAs($this->admin)
            ->deleteJson('/charts-of-accounts-items/' . $item->id)
            ->assertJson(['status' => true]);

        // 行仍在表里，只是 deleted_at 被填上——硬删会撞 expense_* 的外键
        $raw = DB::table('chart_of_account_items')->where('id', $item->id)->first();
        $this->assertNotNull($raw, '软删应保留数据行');
        $this->assertNotNull($raw->deleted_at, 'deleted_at 应被填充');
    }

    /** @test */
    public function 被费用类别引用的科目拒绝删除并给出真实原因(): void
    {
        $item = $this->makeItem();

        ExpenseCategory::create([
            'name'                      => '房租',
            'chart_of_account_item_id'  => $item->id,
            '_who_added'                => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson('/charts-of-accounts-items/' . $item->id);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => false,
                'message' => __('charts_of_accounts.chart_of_accounts_in_use'),
            ]);

        $this->assertNotNull(ChartOfAccountItem::find($item->id), '被引用的科目不应被删除');
    }

    /** @test */
    public function 被付款记录引用的科目拒绝删除(): void
    {
        $item = $this->makeItem();

        DB::table('expense_payments')->insert([
            'payment_account_id' => $item->id,
            '_who_added'         => $this->admin->id,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->actingAs($this->admin)
            ->deleteJson('/charts-of-accounts-items/' . $item->id)
            ->assertJson(['status' => false]);

        $this->assertNotNull(ChartOfAccountItem::find($item->id));
    }

    /** @test */
    public function 已删科目不再出现在所属分类下(): void
    {
        $kept    = $this->makeItem('库存现金');
        $removed = $this->makeItem('银行存款');

        $this->actingAs($this->admin)
            ->deleteJson('/charts-of-accounts-items/' . $removed->id)
            ->assertJson(['status' => true]);

        // 会计科目表页面是靠 $cat->Items 渲染的，这里锁住关联层的过滤
        $names = $this->category->fresh()->Items->pluck('name')->all();

        $this->assertSame(['库存现金'], $names);
        $this->assertSame($kept->name, '库存现金');
    }

    /** @test */
    public function 没有会计权限的账号删不了(): void
    {
        $item = $this->makeItem();

        $otherRole = Role::create(['name' => 'Receptionist', 'slug' => 'receptionist']);
        $receptionist = User::factory()->create([
            'role_id'   => $otherRole->id,
            'branch_id' => $this->admin->branch_id,
            'password'  => bcrypt('password'),
            'status'    => 'active',
        ]);

        $this->actingAs($receptionist)
            ->deleteJson('/charts-of-accounts-items/' . $item->id)
            ->assertStatus(403);

        $this->assertNotNull(ChartOfAccountItem::find($item->id));
    }
}
