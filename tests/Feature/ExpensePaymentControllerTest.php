<?php

namespace Tests\Feature;

use App\AccountingEquation;
use App\Branch;
use App\ChartOfAccountCategory;
use App\ChartOfAccountItem;
use App\Expense;
use App\ExpensePayment;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\Supplier;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 支出付款（/expense-payments），此前零测试覆盖。
 *
 * 建这组用例时撞出两个线上就会踩的问题：
 *   1. store() 用 $request->only([...]) 取参数时漏了 expense_id，而
 *      ExpensePaymentService::createPayment() 里要读 $data['expense_id'] ——
 *      表单明明提交了这个隐藏字段，却在控制器这一层被丢掉，付款必炸。
 *   2. 付款方式表单里有「Bank Wire Transfer」，建表 enum 却只有
 *      Cash / Mobile Money / Cheque / Online Wallet，选它写不进去。
 */
class ExpensePaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private User $outsider;
    private Expense $expense;
    private ChartOfAccountItem $account;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $financeRole  = Role::create(['name' => 'Finance', 'slug' => 'finance']);
        $outsiderRole = Role::create(['name' => 'Nurse', 'slug' => 'nurse']);

        $perm = Permission::create([
            'name'   => '管理费用',
            'slug'   => 'manage-expenses',
            'module' => '财务管理',
        ]);
        RolePermission::create(['role_id' => $financeRole->id, 'permission_id' => $perm->id]);

        $this->finance = User::factory()->create([
            'role_id'   => $financeRole->id,
            'branch_id' => $branch->id,
            'status'    => 'active',
        ]);

        $this->outsider = User::factory()->create([
            'role_id'   => $outsiderRole->id,
            'branch_id' => $branch->id,
            'status'    => 'active',
        ]);

        $supplier = Supplier::create([
            'name'       => '牙科耗材供应商',
            '_who_added' => $this->finance->id,
        ]);

        $this->expense = Expense::create([
            'purchase_no'   => 'PO20260901',
            'supplier_id'   => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'branch_id'     => $branch->id,
            '_who_added'    => $this->finance->id,
        ]);

        $equation = AccountingEquation::create([
            'name'       => '资产',
            'sort_by'    => 1,
            'active_tab' => true,
            '_who_added' => $this->finance->id,
        ]);
        $category = ChartOfAccountCategory::create([
            'name'                   => '流动资产',
            'accounting_equation_id' => $equation->id,
            '_who_added'             => $this->finance->id,
        ]);
        $this->account = ChartOfAccountItem::create([
            'name'                         => '库存现金',
            'chart_of_account_category_id' => $category->id,
            '_who_added'                   => $this->finance->id,
        ]);

        Cache::flush();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'expense_id'      => $this->expense->id,
            'payment_date'    => now()->format('Y-m-d'),
            'amount'          => 500,
            'payment_method'  => 'Cash',
            'payment_account' => $this->account->id,
        ], $overrides);
    }

    /** @test */
    public function 支出付款会落库并挂在对应采购单上(): void
    {
        $response = $this->actingAs($this->finance)
            ->postJson('/expense-payments', $this->payload());

        $response->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('expense_payments', [
            'expense_id'         => $this->expense->id,
            'amount'             => 500,
            'payment_method'     => 'Cash',
            'payment_account_id' => $this->account->id,
            '_who_added'         => $this->finance->id,
        ]);
    }

    /**
     * expense_id 缺失时必须被验证拦下，而不是漏到服务层炸成 500。
     */
    /** @test */
    public function 缺少采购单时返回校验错误而不是500(): void
    {
        $payload = $this->payload();
        unset($payload['expense_id']);

        $this->actingAs($this->finance)
            ->postJson('/expense-payments', $payload)
            ->assertStatus(422);

        $this->assertSame(0, ExpensePayment::count());
    }

    /** @test */
    public function 其余必填项缺失时拒绝(): void
    {
        foreach (['payment_date', 'amount', 'payment_method', 'payment_account'] as $field) {
            $payload = $this->payload();
            unset($payload[$field]);

            $this->actingAs($this->finance)
                ->postJson('/expense-payments', $payload)
                ->assertStatus(422);
        }

        $this->assertSame(0, ExpensePayment::count());
    }

    /**
     * 表单列出的四种付款方式都必须真的写得进去。
     *
     * 建表 enum 只有 Cash / Mobile Money / Cheque / Online Wallet，
     * 而表单第四项给的是 Bank Wire Transfer —— 两边对不上。
     */
    /** @test */
    public function 表单上列出的付款方式都能落库(): void
    {
        $methods = ['Cash', 'Mobile Money', 'Cheque', 'Bank Wire Transfer'];

        foreach ($methods as $method) {
            $this->actingAs($this->finance)
                ->postJson('/expense-payments', $this->payload(['payment_method' => $method]))
                ->assertStatus(200)
                ->assertJson(['status' => true]);

            $this->assertDatabaseHas('expense_payments', [
                'expense_id'     => $this->expense->id,
                'payment_method' => $method,
            ]);
        }
    }

    /** @test */
    public function 无费用管理权限的角色付不了款(): void
    {
        $this->actingAs($this->outsider)
            ->postJson('/expense-payments', $this->payload())
            ->assertStatus(403);

        $this->assertSame(0, ExpensePayment::count());
    }

    /** @test */
    public function 删除支出付款走软删(): void
    {
        $payment = ExpensePayment::create([
            'payment_date'       => now()->format('Y-m-d'),
            'amount'             => 500,
            'payment_method'     => 'Cash',
            'payment_account_id' => $this->account->id,
            'expense_id'         => $this->expense->id,
            '_who_added'         => $this->finance->id,
        ]);

        $this->actingAs($this->finance)
            ->deleteJson('/expense-payments/' . $payment->id)
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertNull(ExpensePayment::find($payment->id));
        $this->assertNotNull(ExpensePayment::withTrashed()->find($payment->id)->deleted_at);
    }
}
