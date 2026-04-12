# Patient Billing Tab Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 将患者详情的「发票」Tab 替换为完整「收费」Tab，接入现有 `billing_tab.blade.php`，并通过右侧滑出面板补齐 3 项缺失功能：账单人员修改（3.4.9）、欠费补收与再优惠（3.4.7）、收费单收款方式修改（3.4.11）。

**Architecture:** 后端新增 `getBillingDetail()` / `updateStaff()` / `addOverduePayment()` 三个 Service 方法，对应三个 Controller 方法和两条新路由，并做一次 invoices 表迁移（新增人员字段）；前端在已有 `patient-billing.css` 追加面板动画样式，在 `billing_tab.blade.php` 追加面板 DOM，在 `patient_billing.js` 追加面板逻辑，最后在 `patients/show.blade.php` 完成接线。

**Tech Stack:** PHP 8.2+, Laravel 11, PHPUnit (Feature tests), Bootstrap 4, jQuery, Yajra DataTables, CSS transform 滑出面板（无需 Bootstrap 5）

---

## File Map

| 文件 | 变更 |
|------|------|
| `database/migrations/<ts>_add_staff_to_invoices_table.php` | **新建** |
| `tests/Feature/PatientBillingTabTest.php` | **新建** |
| `app/Invoice.php` | 修改：$fillable + relationships |
| `app/Services/InvoiceService.php` | 修改：3 个新方法 |
| `app/Http/Controllers/InvoiceController.php` | 修改：3 个新方法 |
| `app/Http/Controllers/InvoicePaymentController.php` | 修改：update() 支持仅更新收款方式 |
| `routes/web.php` | 修改：2 条新路由 |
| `resources/lang/zh-CN/invoices.php` | 修改：新增 key |
| `resources/lang/en/invoices.php` | 修改：新增 key |
| `public/css/patient-billing.css` | 修改：追加面板样式 |
| `resources/views/patients/partials/billing_tab.blade.php` | 修改：面板 DOM + DataTable row 属性 |
| `public/include_js/patient_billing.js` | 修改：面板逻辑 + AJAX |
| `resources/views/patients/show.blade.php` | 修改：替换发票 Tab |

---

## Task 1: DB Migration + Invoice Model

**Files:**
- Create: `database/migrations/<ts>_add_staff_to_invoices_table.php`
- Modify: `app/Invoice.php`

- [ ] **Step 1: 生成迁移文件**

```bash
cd /Users/xudong/git/dental-medical-managment-system
php artisan make:migration add_staff_to_invoices_table
```

预期：`database/migrations/xxxx_xx_xx_xxxxxx_add_staff_to_invoices_table.php` 已创建。

- [ ] **Step 2: 填写迁移内容**

打开刚生成的迁移文件，将 `up()` 和 `down()` 替换为：

```php
public function up(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->unsignedBigInteger('doctor_id')->nullable()->after('medical_case_id');
        $table->unsignedBigInteger('nurse_id')->nullable()->after('doctor_id');
        $table->unsignedBigInteger('assistant_id')->nullable()->after('nurse_id');

        $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
        $table->foreign('nurse_id')->references('id')->on('users')->nullOnDelete();
        $table->foreign('assistant_id')->references('id')->on('users')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->dropForeign(['doctor_id']);
        $table->dropForeign(['nurse_id']);
        $table->dropForeign(['assistant_id']);
        $table->dropColumn(['doctor_id', 'nurse_id', 'assistant_id']);
    });
}
```

- [ ] **Step 3: 执行迁移**

```bash
php artisan migrate
```

预期输出包含：`add_staff_to_invoices_table ........ XX ms DONE`

- [ ] **Step 4: 更新 Invoice 模型**

打开 `app/Invoice.php`。

在 `$fillable` 数组末尾的 `'billing_mode'` 之后追加三个字段：

```php
// 在 'billing_mode', 后面
'doctor_id',
'nurse_id',
'assistant_id',
```

在现有 `creditApprovedBy()` 方法之后追加三个关系：

```php
public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\User::class, 'doctor_id');
}

public function nurse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\User::class, 'nurse_id');
}

public function assistant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\User::class, 'assistant_id');
}
```

- [ ] **Step 5: 语法检查**

```bash
php -l app/Invoice.php
```

预期：`No syntax errors detected in app/Invoice.php`

- [ ] **Step 6: Commit**

```bash
git add database/migrations app/Invoice.php
git commit -m "feat: add doctor/nurse/assistant staff fields to invoices table"
```

---

## Task 2: Feature Test 骨架

**Files:**
- Create: `tests/Feature/PatientBillingTabTest.php`

- [ ] **Step 1: 创建测试文件**

```bash
php artisan make:test PatientBillingTabTest
```

- [ ] **Step 2: 填写测试骨架**

用以下内容替换 `tests/Feature/PatientBillingTabTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientBillingTabTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Invoice $invoice;
    private Invoice $overdueInvoice;
    private InvoicePayment $payment;
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::factory()->create();

        $this->admin = User::factory()->create(['branch_id' => $branch->id]);
        $this->doctor = User::factory()->create(['branch_id' => $branch->id]);

        $role = Role::create(['name' => 'admin-billing-test', 'display_name' => 'Admin']);
        foreach (['edit-invoices', 'view-invoices'] as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'display_name' => $permName, 'guard_name' => 'web']);
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        }
        $this->admin->roles()->attach($role);

        $this->patient = Patient::factory()->create(['branch_id' => $branch->id]);

        $this->invoice = Invoice::factory()->create([
            'patient_id' => $this->patient->id,
            'total_amount' => '500.00',
            'paid_amount' => '500.00',
            'outstanding_amount' => '0.00',
            'payment_status' => 'paid',
            'branch_id' => $branch->id,
        ]);

        $this->overdueInvoice = Invoice::factory()->create([
            'patient_id' => $this->patient->id,
            'total_amount' => '800.00',
            'paid_amount' => '300.00',
            'outstanding_amount' => '500.00',
            'payment_status' => 'partial',
            'branch_id' => $branch->id,
        ]);

        $this->payment = InvoicePayment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => '500.00',
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
        ]);
    }

    // ── Tests will be added in Tasks 3, 5, 6 ──
}
```

- [ ] **Step 3: 运行骨架确认无报错**

```bash
php artisan test tests/Feature/PatientBillingTabTest.php
```

预期：`Tests: 0 passed` 无错误（空测试类正常通过）。

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/PatientBillingTabTest.php
git commit -m "test: add PatientBillingTabTest scaffold"
```

---

## Task 3: InvoiceService — getBillingDetail() + updateStaff()

**Files:**
- Modify: `tests/Feature/PatientBillingTabTest.php` (追加测试)
- Modify: `app/Services/InvoiceService.php` (追加方法)

- [ ] **Step 1: 写账单详情接口的失败测试**

在 `PatientBillingTabTest.php` 的 `// ── Tests will be added` 注释处追加：

```php
/** @test */
public function billing_detail_returns_invoice_with_staff_and_user_list(): void
{
    $this->invoice->update(['doctor_id' => $this->doctor->id]);

    $response = $this->actingAs($this->admin)
        ->getJson('/invoices/' . $this->invoice->id . '/billing-detail');

    $response->assertStatus(200)
             ->assertJsonPath('status', 1)
             ->assertJsonPath('data.id', $this->invoice->id)
             ->assertJsonPath('data.doctor_id', $this->doctor->id)
             ->assertJsonStructure(['data' => [
                 'id', 'invoice_no', 'invoice_date',
                 'total_amount', 'paid_amount', 'outstanding_amount',
                 'payment_status', 'doctor_id', 'nurse_id', 'assistant_id',
                 'users',
             ]]);
}

/** @test */
public function update_staff_fields_on_invoice(): void
{
    $response = $this->actingAs($this->admin)
        ->patchJson('/invoices/' . $this->invoice->id, [
            'doctor_id'    => $this->doctor->id,
            'nurse_id'     => null,
            'assistant_id' => null,
        ]);

    $response->assertStatus(200)
             ->assertJsonPath('status', 1);

    $this->assertDatabaseHas('invoices', [
        'id'        => $this->invoice->id,
        'doctor_id' => $this->doctor->id,
        'nurse_id'  => null,
    ]);
}

/** @test */
public function update_staff_rejects_nonexistent_user(): void
{
    $response = $this->actingAs($this->admin)
        ->patchJson('/invoices/' . $this->invoice->id, [
            'doctor_id' => 99999,
        ]);

    $response->assertStatus(422);
}
```

- [ ] **Step 2: 运行测试确认失败**

```bash
php artisan test tests/Feature/PatientBillingTabTest.php --filter=billing_detail
```

预期：FAIL — 404 或路由未找到。

- [ ] **Step 3: 在 InvoiceService 追加两个方法**

打开 `app/Services/InvoiceService.php`，在 `getPatientReceipts()` 方法之后追加：

```php
/**
 * 账单详情面板数据（3.4.9 / 3.4.7 面板用）
 */
public function getBillingDetail(int $invoiceId): array
{
    $invoice = \App\Invoice::with(['doctor', 'nurse', 'assistant'])
        ->findOrFail($invoiceId);

    $branchId = \Illuminate\Support\Facades\Auth::user()->branch_id;
    $users = \App\User::where('branch_id', $branchId)
        ->whereNull('deleted_at')
        ->select('id', 'othername as name')
        ->orderBy('othername')
        ->get()
        ->toArray();

    return [
        'id'               => $invoice->id,
        'invoice_no'       => $invoice->invoice_no,
        'invoice_date'     => $invoice->invoice_date,
        'total_amount'     => $invoice->total_amount,
        'paid_amount'      => $invoice->paid_amount,
        'outstanding_amount' => $invoice->outstanding_amount,
        'payment_status'   => $invoice->payment_status,
        'doctor_id'        => $invoice->doctor_id,
        'nurse_id'         => $invoice->nurse_id,
        'assistant_id'     => $invoice->assistant_id,
        'users'            => $users,
    ];
}

/**
 * 更新账单人员字段（3.4.9）
 */
public function updateStaff(int $invoiceId, array $data): bool
{
    return (bool) \App\Invoice::where('id', $invoiceId)->update([
        'doctor_id'    => $data['doctor_id'] ?? null,
        'nurse_id'     => $data['nurse_id'] ?? null,
        'assistant_id' => $data['assistant_id'] ?? null,
    ]);
}
```

- [ ] **Step 4: 语法检查**

```bash
php -l app/Services/InvoiceService.php
```

预期：`No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add app/Services/InvoiceService.php
git commit -m "feat: add getBillingDetail and updateStaff to InvoiceService"
```

---

## Task 4: InvoiceController — billingDetail() + update() + 路由

**Files:**
- Modify: `app/Http/Controllers/InvoiceController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: 在 InvoiceController 追加 billingDetail() 并实现 update()**

打开 `app/Http/Controllers/InvoiceController.php`。

**实现空的 update() 方法：**

```php
public function update(Request $request, $invoice)
{
    $validator = Validator::make($request->all(), [
        'doctor_id'    => 'nullable|exists:users,id',
        'nurse_id'     => 'nullable|exists:users,id',
        'assistant_id' => 'nullable|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first(), 'status' => 0], 422);
    }

    $updated = $this->invoiceService->updateStaff(
        (int) $invoice,
        $request->only(['doctor_id', 'nurse_id', 'assistant_id'])
    );

    if ($updated) {
        return response()->json(['message' => __('invoices.staff_updated'), 'status' => 1]);
    }
    return response()->json(['message' => __('messages.error_occurred_later'), 'status' => 0]);
}
```

**追加 billingDetail() 方法（紧接 update() 之后）：**

```php
public function billingDetail($id)
{
    $data = $this->invoiceService->getBillingDetail((int) $id);
    return response()->json(['status' => 1, 'data' => $data]);
}
```

- [ ] **Step 2: 在 routes/web.php 添加新路由**

在 `Route::resource('invoices', 'InvoiceController');` 这行**之前**插入：

```php
Route::get('invoices/{id}/billing-detail', 'InvoiceController@billingDetail');
Route::post('invoices/{id}/add-overdue-payment', 'InvoiceController@addOverduePayment');
```

> 必须放在 resource() 之前，否则 `{id}/billing-detail` 会被 resource 的 show 路由拦截。

- [ ] **Step 3: 语法检查**

```bash
php -l app/Http/Controllers/InvoiceController.php
php artisan route:list | grep billing-detail
```

预期：路由列表中出现 `invoices/{id}/billing-detail` 和 `invoices/{id}/add-overdue-payment`。

- [ ] **Step 4: 运行账单详情和人员更新测试**

```bash
php artisan test tests/Feature/PatientBillingTabTest.php \
  --filter="billing_detail|update_staff"
```

预期：3 个测试全部 PASS。

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/InvoiceController.php routes/web.php
git commit -m "feat: add billingDetail and updateStaff endpoints to InvoiceController"
```

---

## Task 5: InvoiceService — addOverduePayment()

**Files:**
- Modify: `tests/Feature/PatientBillingTabTest.php` (追加测试)
- Modify: `app/Services/InvoiceService.php` (追加方法)
- Modify: `app/Http/Controllers/InvoiceController.php` (追加方法)

- [ ] **Step 1: 写欠费补收的失败测试**

在 `PatientBillingTabTest.php` 已有测试之后追加：

```php
/** @test */
public function add_overdue_payment_creates_payment_and_updates_invoice(): void
{
    $response = $this->actingAs($this->admin)
        ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
            'amount'         => '200.00',
            'payment_method' => 'Cash',
        ]);

    $response->assertStatus(200)
             ->assertJsonPath('status', 1)
             ->assertJsonPath('data.new_outstanding', '300.00');

    $this->assertDatabaseHas('invoice_payments', [
        'invoice_id'     => $this->overdueInvoice->id,
        'amount'         => '200.00',
        'payment_method' => 'Cash',
    ]);

    $this->assertDatabaseHas('invoices', [
        'id'                => $this->overdueInvoice->id,
        'paid_amount'       => '500.00',
        'outstanding_amount'=> '300.00',
        'payment_status'    => 'partial',
    ]);
}

/** @test */
public function add_overdue_payment_with_discount_reduces_total(): void
{
    $response = $this->actingAs($this->admin)
        ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
            'amount'              => '400.00',
            'additional_discount' => '100.00',
            'payment_method'      => 'Cash',
        ]);

    $response->assertStatus(200)
             ->assertJsonPath('status', 1)
             ->assertJsonPath('data.new_outstanding', '0.00');

    $this->assertDatabaseHas('invoices', [
        'id'                => $this->overdueInvoice->id,
        'outstanding_amount'=> '0.00',
        'payment_status'    => 'paid',
    ]);
}

/** @test */
public function add_overdue_payment_rejects_amount_exceeding_outstanding(): void
{
    $response = $this->actingAs($this->admin)
        ->postJson('/invoices/' . $this->overdueInvoice->id . '/add-overdue-payment', [
            'amount'         => '600.00',
            'payment_method' => 'Cash',
        ]);

    $response->assertStatus(422);
}

/** @test */
public function add_overdue_payment_rejects_on_fully_paid_invoice(): void
{
    $response = $this->actingAs($this->admin)
        ->postJson('/invoices/' . $this->invoice->id . '/add-overdue-payment', [
            'amount'         => '10.00',
            'payment_method' => 'Cash',
        ]);

    $response->assertStatus(422);
}
```

- [ ] **Step 2: 运行测试确认失败**

```bash
php artisan test tests/Feature/PatientBillingTabTest.php --filter=add_overdue
```

预期：4 个测试 FAIL（路由存在但 controller 方法未实现）。

- [ ] **Step 3: 在 InvoiceService 追加 addOverduePayment()**

在 `updateStaff()` 之后追加：

```php
/**
 * 欠费补收与再优惠（3.4.7）
 * @throws \InvalidArgumentException
 */
public function addOverduePayment(int $invoiceId, array $data): array
{
    $invoice = \App\Invoice::findOrFail($invoiceId);

    $amount             = (string) ($data['amount'] ?? '0');
    $additionalDiscount = (string) ($data['additional_discount'] ?? '0');
    $outstanding        = (string) $invoice->outstanding_amount;

    // 至少一项大于 0
    if (bccomp($amount, '0', 2) <= 0 && bccomp($additionalDiscount, '0', 2) <= 0) {
        throw new \InvalidArgumentException(__('invoices.overdue_amount_required'));
    }

    // 补收 + 再优惠不能超过欠费
    $total = bcadd($amount, $additionalDiscount, 2);
    if (bccomp($total, $outstanding, 2) > 0) {
        throw new \InvalidArgumentException(__('invoices.overdue_amount_exceeds'));
    }

    // 再优惠：减少 total_amount，Invoice::saving() 重算 outstanding
    if (bccomp($additionalDiscount, '0', 2) > 0) {
        $invoice->discount_amount = bcadd((string) $invoice->discount_amount, $additionalDiscount, 2);
        $invoice->total_amount    = bcsub((string) $invoice->total_amount, $additionalDiscount, 2);
    }

    // 创建补收记录
    if (bccomp($amount, '0', 2) > 0) {
        \App\InvoicePayment::create([
            'invoice_id'           => $invoiceId,
            'amount'               => $amount,
            'payment_method'       => $data['payment_method'],
            'payment_date'         => $data['payment_date'] ?? now()->toDateString(),
            'cheque_no'            => $data['cheque_no'] ?? null,
            'bank_name'            => $data['bank_name'] ?? null,
            'insurance_company_id' => $data['insurance_company_id'] ?? null,
            'self_account_id'      => $data['self_account_id'] ?? null,
            'branch_id'            => \Illuminate\Support\Facades\Auth::user()->branch_id,
            '_who_added'           => \Illuminate\Support\Facades\Auth::user()->id,
        ]);

        $invoice->paid_amount = bcadd((string) $invoice->paid_amount, $amount, 2);
    }

    // saving() hook 自动重算 outstanding_amount 和 payment_status
    $invoice->save();

    return ['new_outstanding' => $invoice->outstanding_amount];
}
```

- [ ] **Step 4: 在 InvoiceController 追加 addOverduePayment()**

紧接 `billingDetail()` 方法之后追加：

```php
public function addOverduePayment(Request $request, $id)
{
    $invoice = \App\Invoice::find($id);
    if (!$invoice) {
        return response()->json(['message' => __('messages.record_not_found'), 'status' => 0], 404);
    }

    $outstanding = (string) $invoice->outstanding_amount;
    $amountInput = (string) ($request->input('amount', '0'));
    $discountInput = (string) ($request->input('additional_discount', '0'));

    $validator = Validator::make($request->all(), [
        'amount'              => 'required_without:additional_discount|nullable|numeric|min:0',
        'additional_discount' => 'nullable|numeric|min:0',
        'payment_method'      => 'required_with:amount|nullable|string',
        'payment_date'        => 'nullable|date',
        'cheque_no'           => 'required_if:payment_method,Cheque',
        'bank_name'           => 'required_if:payment_method,Cheque',
        'insurance_company_id'=> 'required_if:payment_method,Insurance|nullable|exists:insurance_companies,id',
        'self_account_id'     => 'required_if:payment_method,Self Account|nullable|exists:self_accounts,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first(), 'status' => 0], 422);
    }

    // 前置业务校验
    if (bccomp($outstanding, '0', 2) <= 0) {
        return response()->json(['message' => __('invoices.invoice_already_paid'), 'status' => 0], 422);
    }

    $total = bcadd($amountInput, $discountInput, 2);
    if (bccomp($total, $outstanding, 2) > 0) {
        return response()->json(['message' => __('invoices.overdue_amount_exceeds'), 'status' => 0], 422);
    }

    try {
        $result = $this->invoiceService->addOverduePayment((int) $id, $request->all());
        return response()->json([
            'message' => __('invoices.overdue_payment_success'),
            'status'  => 1,
            'data'    => $result,
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage(), 'status' => 0], 422);
    }
}
```

- [ ] **Step 5: 语法检查**

```bash
php -l app/Services/InvoiceService.php
php -l app/Http/Controllers/InvoiceController.php
```

- [ ] **Step 6: 运行欠费补收测试**

```bash
php artisan test tests/Feature/PatientBillingTabTest.php --filter=add_overdue
```

预期：4 个测试全部 PASS。

- [ ] **Step 7: Commit**

```bash
git add app/Services/InvoiceService.php app/Http/Controllers/InvoiceController.php \
        tests/Feature/PatientBillingTabTest.php
git commit -m "feat: add addOverduePayment endpoint with bcmath calculations"
```

---

## Task 6: InvoicePaymentController — update() 支持仅修改收款方式

**Files:**
- Modify: `tests/Feature/PatientBillingTabTest.php`
- Modify: `app/Http/Controllers/InvoicePaymentController.php`

- [ ] **Step 1: 写收款方式修改的失败测试**

追加到 `PatientBillingTabTest.php`：

```php
/** @test */
public function update_payment_method_without_changing_amount(): void
{
    $response = $this->actingAs($this->admin)
        ->putJson('/payments/' . $this->payment->id, [
            'payment_method' => 'WeChat',
        ]);

    $response->assertStatus(200)
             ->assertJsonPath('status', true);

    $this->assertDatabaseHas('invoice_payments', [
        'id'             => $this->payment->id,
        'payment_method' => 'WeChat',
        'amount'         => '500.00', // 金额未变
    ]);
}

/** @test */
public function update_payment_method_rejects_cheque_without_cheque_no(): void
{
    $response = $this->actingAs($this->admin)
        ->putJson('/payments/' . $this->payment->id, [
            'payment_method' => 'Cheque',
            // 缺少 cheque_no 和 bank_name
        ]);

    $response->assertStatus(422);
}
```

- [ ] **Step 2: 运行测试确认失败**

```bash
php artisan test tests/Feature/PatientBillingTabTest.php --filter=update_payment_method
```

预期：FAIL（当前 controller 要求 amount + payment_date）。

- [ ] **Step 3: 修改 InvoicePaymentController::update()**

将 `update()` 方法替换为：

```php
public function update(Request $request, $id)
{
    $payment = \App\InvoicePayment::find($id);
    if (!$payment) {
        return response()->json(['message' => __('messages.record_not_found'), 'status' => false], 404);
    }

    $validator = Validator::make($request->all(), [
        'payment_method'       => 'required|string',
        'cheque_no'            => 'required_if:payment_method,Cheque',
        'bank_name'            => 'required_if:payment_method,Cheque',
        'insurance_company_id' => 'required_if:payment_method,Insurance|nullable|exists:insurance_companies,id',
        'self_account_id'      => 'required_if:payment_method,Self Account|nullable|exists:self_accounts,id',
        'amount'               => 'nullable|numeric|min:0',
        'payment_date'         => 'nullable|date',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => $validator->errors()->first(), 'status' => false], 422);
    }

    // 保留原金额和日期（除非 request 显式提供）
    $data = array_merge(
        [
            'amount'       => $payment->amount,
            'payment_date' => $payment->payment_date,
        ],
        $request->only([
            'payment_method', 'amount', 'payment_date',
            'cheque_no', 'bank_name', 'account_name',
            'insurance_company_id', 'self_account_id',
        ])
    );

    $status = $this->invoicePaymentService->updatePayment((int) $id, $data);
    if ($status) {
        return response()->json(['message' => __('invoices.payment_method_updated'), 'status' => true]);
    }
    return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
}
```

- [ ] **Step 4: 运行测试**

```bash
php artisan test tests/Feature/PatientBillingTabTest.php --filter=update_payment_method
```

预期：2 个测试 PASS。

- [ ] **Step 5: 运行全部测试确认无回归**

```bash
php artisan test
```

预期：全部 PASS（包含原有测试）。

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/InvoicePaymentController.php \
        tests/Feature/PatientBillingTabTest.php
git commit -m "fix: allow updating payment method without requiring amount/date fields"
```

---

## Task 7: i18n Keys

**Files:**
- Modify: `resources/lang/zh-CN/invoices.php`
- Modify: `resources/lang/en/invoices.php`

- [ ] **Step 1: 在 zh-CN/invoices.php 末尾的 `];` 之前追加**

```php
    // ── 患者收费 Tab 面板 (3.4.7 / 3.4.9 / 3.4.11) ──
    'panel_invoice_detail'    => '账单详情',
    'modify_staff'            => '修改人员',
    'doctor'                  => '医生',
    'nurse'                   => '护士',
    'assistant'               => '助理',
    'overdue_payment'         => '欠费处理',
    'supplement_amount'       => '补收金额',
    'additional_discount'     => '再优惠金额',
    'additional_discount_tip' => '填写后从欠费中直接减免，无需实际收款',
    'modify_payment_method'   => '修改收款方式',
    'panel_receipt_detail'    => '收费单详情',
    'receipt_amount_readonly' => '金额（只读）',
    'staff_updated'           => '人员已更新',
    'overdue_payment_success' => '补收成功',
    'payment_method_updated'  => '收款方式已修改',
    'invoice_already_paid'    => '该账单已��清，无需补收',
    'overdue_amount_required' => '补收金额和再优惠金额不能同时为零',
    'overdue_amount_exceeds'  => '补收金额与再优惠之和不能超过欠费金额',
    'no_invoices_hint'        => '暂无账单，可先在划价中新增收费项目',
    'no_receipts_hint'        => '暂无收费记录',
    'invoice_settled'         => '该账单已结清',
    'panel_load_failed'       => '加载失败，请重试',
```

- [ ] **Step 2: 在 en/invoices.php 末尾的 `];` 之前追加**

```php
    // ── Patient Billing Tab Panel (3.4.7 / 3.4.9 / 3.4.11) ──
    'panel_invoice_detail'    => 'Invoice Detail',
    'modify_staff'            => 'Edit Staff',
    'doctor'                  => 'Doctor',
    'nurse'                   => 'Nurse',
    'assistant'               => 'Assistant',
    'overdue_payment'         => 'Overdue Payment',
    'supplement_amount'       => 'Payment Amount',
    'additional_discount'     => 'Additional Discount',
    'additional_discount_tip' => 'Will be deducted from outstanding without actual collection',
    'modify_payment_method'   => 'Edit Payment Method',
    'panel_receipt_detail'    => 'Receipt Detail',
    'receipt_amount_readonly' => 'Amount (read-only)',
    'staff_updated'           => 'Staff updated',
    'overdue_payment_success' => 'Payment recorded',
    'payment_method_updated'  => 'Payment method updated',
    'invoice_already_paid'    => 'Invoice is fully paid',
    'overdue_amount_required' => 'Amount or additional discount must be greater than zero',
    'overdue_amount_exceeds'  => 'Total cannot exceed outstanding amount',
    'no_invoices_hint'        => 'No invoices yet. Add services in the Billing tab.',
    'no_receipts_hint'        => 'No receipts yet',
    'invoice_settled'         => 'Invoice fully settled',
    'panel_load_failed'       => 'Load failed, please retry',
```

- [ ] **Step 3: 语法检查**

```bash
php -l resources/lang/zh-CN/invoices.php
php -l resources/lang/en/invoices.php
```

- [ ] **Step 4: Commit**

```bash
git add resources/lang/zh-CN/invoices.php resources/lang/en/invoices.php
git commit -m "feat: add billing panel i18n keys to zh-CN and en"
```

---

## Task 8: CSS — 滑出面板样式

**Files:**
- Modify: `public/css/patient-billing.css`

- [ ] **Step 1: 在 patient-billing.css 末尾追加面板样式**

```css
/* ══ Right Slide Panel (账单 / 收费单 详情面板) ══ */

/* Overlay */
.billing-panel-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    z-index: 1040;
}
.billing-panel-overlay.active {
    display: block;
}

/* Panel */
.billing-side-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 420px;
    max-width: 100vw;
    height: 100%;
    background: #fff;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.12);
    z-index: 1050;
    overflow-y: auto;
    transform: translateX(100%);
    transition: transform 0.25s ease;
}
.billing-side-panel.open {
    transform: translateX(0);
}

/* Panel header */
.billing-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e7ecf1;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}
.billing-panel-header h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #333;
}
.billing-panel-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #999;
    cursor: pointer;
    line-height: 1;
    padding: 0 4px;
}
.billing-panel-close:hover {
    color: #333;
}

/* Panel body */
.billing-panel-body {
    padding: 20px;
}
.billing-panel-section {
    margin-bottom: 20px;
}
.billing-panel-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f0f3f6;
}
.billing-panel-section-title.overdue {
    color: #e67e22;
}

/* Meta info row */
.billing-panel-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
}
.billing-panel-meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}
.billing-panel-meta-row .label {
    color: #888;
}
.billing-panel-meta-row .value {
    font-weight: 600;
    color: #333;
}
.billing-panel-meta-row .value.overdue {
    color: #e67e22;
}

/* Panel form */
.billing-panel-body .form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #555;
}
.billing-panel-body .form-control {
    font-size: 13px;
}
.billing-panel-body .form-text {
    font-size: 11px;
    color: #999;
}

/* Save button */
.billing-panel-save {
    width: 100%;
    padding: 10px;
    margin-top: 8px;
}

/* DataTable row cursor */
#patient_invoices_table tbody tr,
#patient_receipts_table tbody tr {
    cursor: pointer;
}
#patient_invoices_table tbody tr:hover,
#patient_receipts_table tbody tr:hover {
    background-color: #f5f8ff;
}
```

- [ ] **Step 2: Commit**

```bash
git add public/css/patient-billing.css
git commit -m "feat: add slide panel CSS to patient-billing.css"
```

---

## Task 9: billing_tab.blade.php — 面板 DOM + DataTable 行属性

**Files:**
- Modify: `resources/views/patients/partials/billing_tab.blade.php`

- [ ] **Step 1: 在「账单」子 Tab 的 DataTable 定义中追加 id 和 outstanding 列**

找到账单子 Tab (`#billing_sub_bills`) 中的 DataTable，将表头中已有的 `viewBtn` 列之后，确认 table 的 id 是 `patient_invoices_table`。

在整个 `billing_tab.blade.php` 文件**末尾** `{{-- end billing tab --}}` 注释之前，追加面板 DOM：

```blade
{{-- ══ Right Side Panel (账单详情 / 收费单详情) ══ --}}
<div class="billing-panel-overlay" id="billingPanelOverlay"></div>

<div class="billing-side-panel" id="billingSidePanel" role="dialog" aria-modal="true">
    <div class="billing-panel-header">
        <h4 id="billingPanelTitle">{{ __('invoices.panel_invoice_detail') }}</h4>
        <button class="billing-panel-close" id="billingPanelClose" aria-label="Close">&#x2715;</button>
    </div>
    <div class="billing-panel-body" id="billingPanelBody">
        {{-- Content rendered by JS --}}
    </div>
</div>
```

- [ ] **Step 2: 将 i18n 翻译注入 JS**

在 billing_tab.blade.php 顶部（`<ul class="nav nav-tabs billing-sub-tabs"` 之前）确认已有 LanguageManager 加载；若无则追加：

```blade
@push('billing_i18n')
<script>
LanguageManager.loadFromPHP(@json(__('invoices')), 'invoices');
</script>
@endpush
```

> 注：若布局已全局加载 invoices 翻译，删去此段，避免重复注入。实现前先查 `layouts/default.blade.php` 是否有 `LanguageManager.loadFromPHP(@json(__('invoices')))` 调用。若已有则跳过本步。

- [ ] **Step 3: 语法检查**

```bash
php artisan view:clear
php artisan route:list | grep billing-detail
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/patients/partials/billing_tab.blade.php
git commit -m "feat: add side panel DOM to billing_tab partial"
```

---

## Task 10: patient_billing.js — 面板核心逻辑

**Files:**
- Modify: `public/include_js/patient_billing.js`

- [ ] **Step 1: 在 BillingModule IIFE 顶部的状态变量区追加面板状态**

找到 `var invoicesTableLoaded = false;` 这一行，在其之后追加：

```javascript
var panelOpen = false;
var panelType = null; // 'invoice' | 'payment'
var PAYMENT_METHODS = [
    { code: 'Cash',         label: LanguageManager.trans('invoices.cash', '现金') },
    { code: 'WeChat',       label: LanguageManager.trans('invoices.wechat', '微信') },
    { code: 'Alipay',       label: LanguageManager.trans('invoices.alipay', '支付宝') },
    { code: 'BankCard',     label: LanguageManager.trans('invoices.bank_card', '银行卡') },
    { code: 'Insurance',    label: LanguageManager.trans('invoices.insurance', '保险') },
    { code: 'Cheque',       label: LanguageManager.trans('invoices.cheque', '支票') },
    { code: 'StoredValue',  label: LanguageManager.trans('invoices.stored_value', '储值') },
    { code: 'Self Account', label: LanguageManager.trans('invoices.self_account', '自费账户') },
];
```

- [ ] **Step 2: 追加面板 open/close/Esc 函数**

在 `return { init: init, ... }` 语句**之前**追加：

```javascript
/* ── Panel Core ── */

function openPanel(title) {
    panelOpen = true;
    $('#billingPanelTitle').text(title);
    $('#billingPanelBody').html(
        '<div style="text-align:center;padding:40px"><i class="fa fa-spinner fa-spin fa-2x"></i></div>'
    );
    $('#billingPanelOverlay').addClass('active');
    $('#billingSidePanel').addClass('open');
    // 焦点进入面板
    setTimeout(function () {
        $('#billingPanelClose').focus();
    }, 260);
}

function closePanel() {
    panelOpen = false;
    $('#billingPanelOverlay').removeClass('active');
    $('#billingSidePanel').removeClass('open');
}

function bindPanelEvents() {
    $(document).on('click', '#billingPanelClose', function () {
        closePanel();
    });
    $(document).on('click', '#billingPanelOverlay', function () {
        closePanel();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && panelOpen) {
            closePanel();
        }
    });
}
```

- [ ] **Step 3: 在 init() 函数内调用 bindPanelEvents()**

找到 `function init(pid, doctorList)` 函数体，在其末尾（`initialized = true;` 之前）追加：

```javascript
bindPanelEvents();
```

- [ ] **Step 4: 语法检查**

```bash
node --check public/include_js/patient_billing.js
```

预期：无输出（无语法错误）。

- [ ] **Step 5: Commit**

```bash
git add public/include_js/patient_billing.js
git commit -m "feat: add panel open/close/Esc core logic to BillingModule"
```

---

## Task 11: patient_billing.js — 面板 AJAX 内容与保存

**Files:**
- Modify: `public/include_js/patient_billing.js`

- [ ] **Step 1: 追加辅助函数 buildPaymentMethodSelect**

在 `bindPanelEvents()` 之后追加：

```javascript
function buildPaymentMethodSelect(name, selectedCode, cssClass) {
    var html = '<select name="' + name + '" class="form-control ' + (cssClass || '') + '">';
    $.each(PAYMENT_METHODS, function (_, m) {
        html += '<option value="' + m.code + '"' + (m.code === selectedCode ? ' selected' : '') + '>'
             + m.label + '</option>';
    });
    html += '</select>';
    return html;
}

function buildExtraPaymentFields(container, code) {
    var $extra = container.find('.payment-extra-fields').empty();
    if (code === 'Cheque') {
        $extra.html(
            '<div class="form-group"><label>' + LanguageManager.trans('invoices.cheque_no', '支票号') + '</label>' +
            '<input type="text" name="cheque_no" class="form-control input-sm"></div>' +
            '<div class="form-group"><label>' + LanguageManager.trans('invoices.bank_name', '银行') + '</label>' +
            '<input type="text" name="bank_name" class="form-control input-sm"></div>'
        );
    }
    // Insurance / Self Account AJAX select 可在此扩展，当前留空保持简洁
}
```

- [ ] **Step 2: 追加 openInvoicePanel()**

```javascript
function openInvoicePanel(invoiceId) {
    panelType = 'invoice';
    openPanel(LanguageManager.trans('invoices.panel_invoice_detail', '账单详情'));

    $.get('/invoices/' + invoiceId + '/billing-detail', function (res) {
        if (!res || res.status !== 1) {
            $('#billingPanelBody').html(
                '<div class="alert alert-danger">' +
                LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
            );
            return;
        }
        var d = res.data;
        var isOverdue = parseFloat(d.outstanding_amount) > 0;

        // 构建用户下拉
        var userOptions = '<option value=""></option>';
        $.each(d.users, function (_, u) {
            userOptions += '<option value="' + u.id + '">' + u.name + '</option>';
        });

        function staffSelect(name, selectedId) {
            return '<select name="' + name + '" class="form-control input-sm">' +
                   userOptions.replace('value="' + selectedId + '"', 'value="' + selectedId + '" selected') +
                   '</select>';
        }

        var html =
            // Meta
            '<div class="billing-panel-meta">' +
            '<div class="billing-panel-meta-row"><span class="label">#</span><span class="value">' + (d.invoice_no || d.id) + '</span></div>' +
            '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.amount', '金额') + '</span><span class="value">¥' + parseFloat(d.total_amount).toFixed(2) + '</span></div>' +
            (isOverdue ? '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.overdue_payment', '欠费') + '</span><span class="value overdue">¥' + parseFloat(d.outstanding_amount).toFixed(2) + '</span></div>' : '') +
            '</div>' +

            // 修改人员
            '<div class="billing-panel-section">' +
            '<div class="billing-panel-section-title">' + LanguageManager.trans('invoices.modify_staff', '修改人员') + '</div>' +
            '<div class="form-group"><label>' + LanguageManager.trans('invoices.doctor', '医生') + '</label>' + staffSelect('doctor_id', d.doctor_id) + '</div>' +
            '<div class="form-group"><label>' + LanguageManager.trans('invoices.nurse', '护士') + '</label>' + staffSelect('nurse_id', d.nurse_id) + '</div>' +
            '<div class="form-group"><label>' + LanguageManager.trans('invoices.assistant', '助理') + '</label>' + staffSelect('assistant_id', d.assistant_id) + '</div>' +
            '<button class="btn btn-primary billing-panel-save" id="panelSaveStaff" data-invoice-id="' + d.id + '">' +
            LanguageManager.trans('common.save', '保存') + '</button>' +
            '</div>';

        // 欠费处理（仅欠费时显示）
        if (isOverdue) {
            html +=
                '<div class="billing-panel-section">' +
                '<div class="billing-panel-section-title overdue">' + LanguageManager.trans('invoices.overdue_payment', '欠费处理') + '</div>' +
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.supplement_amount', '补收金额') + '</label>' +
                '<input type="number" name="overdue_amount" class="form-control input-sm" min="0" step="0.01" placeholder="0.00"></div>' +
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.additional_discount', '再优惠金额') + '</label>' +
                '<input type="number" name="additional_discount" class="form-control input-sm" min="0" step="0.01" placeholder="0.00">' +
                '<span class="form-text">' + LanguageManager.trans('invoices.additional_discount_tip', '填写后从欠费中直接减免') + '</span></div>' +
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.modify_payment_method', '收款方式') + '</label>' +
                buildPaymentMethodSelect('overdue_payment_method', 'Cash') + '</div>' +
                '<div class="payment-extra-fields"></div>' +
                '<button class="btn btn-warning billing-panel-save" id="panelSaveOverdue" data-invoice-id="' + d.id + '" data-outstanding="' + d.outstanding_amount + '">' +
                LanguageManager.trans('common.save', '保存') + '</button>' +
                '</div>';
        }

        $('#billingPanelBody').html(html);

        // 收款方式切换时更新附加字段
        $('#billingPanelBody').on('change', 'select[name="overdue_payment_method"]', function () {
            buildExtraPaymentFields($('#billingPanelBody'), $(this).val());
        });
    }).fail(function () {
        $('#billingPanelBody').html(
            '<div class="alert alert-danger">' +
            LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
        );
    });
}
```

- [ ] **Step 3: 追加 openPaymentPanel()**

```javascript
function openPaymentPanel(paymentId) {
    panelType = 'payment';
    openPanel(LanguageManager.trans('invoices.panel_receipt_detail', '收费单详情'));

    $.get('/payments/' + paymentId + '/edit', function (res) {
        if (!res) {
            $('#billingPanelBody').html(
                '<div class="alert alert-danger">' +
                LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
            );
            return;
        }
        var d = res;
        var html =
            '<div class="billing-panel-meta">' +
            '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.receipt_amount_readonly', '金额') + '</span>' +
            '<span class="value">¥' + parseFloat(d.amount).toFixed(2) + '</span></div>' +
            '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.receipt_date', '日期') + '</span>' +
            '<span class="value">' + (d.payment_date || '-') + '</span></div>' +
            '</div>' +
            '<div class="billing-panel-section">' +
            '<div class="billing-panel-section-title">' + LanguageManager.trans('invoices.modify_payment_method', '修改收款方式') + '</div>' +
            '<div class="form-group">' + buildPaymentMethodSelect('payment_method', d.payment_method) + '</div>' +
            '<div class="payment-extra-fields"></div>' +
            '<button class="btn btn-primary billing-panel-save" id="panelSavePayment" data-payment-id="' + d.id + '">' +
            LanguageManager.trans('common.save', '保存') + '</button>' +
            '</div>';

        $('#billingPanelBody').html(html);
        buildExtraPaymentFields($('#billingPanelBody'), d.payment_method);

        $('#billingPanelBody').on('change', 'select[name="payment_method"]', function () {
            buildExtraPaymentFields($('#billingPanelBody'), $(this).val());
        });
    }).fail(function () {
        $('#billingPanelBody').html(
            '<div class="alert alert-danger">' +
            LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
        );
    });
}
```

- [ ] **Step 4: 追加 bindPanelSaveEvents() 并在 bindPanelEvents() 末尾调用**

```javascript
function bindPanelSaveEvents() {
    // 保存人员
    $(document).on('click', '#panelSaveStaff', function () {
        var $btn = $(this);
        var invoiceId = $btn.data('invoice-id');
        $btn.prop('disabled', true).text(LanguageManager.trans('common.saving', '保存中...'));

        $.ajax({
            url: '/invoices/' + invoiceId,
            method: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                doctor_id:    $('#billingPanelBody select[name="doctor_id"]').val() || null,
                nurse_id:     $('#billingPanelBody select[name="nurse_id"]').val() || null,
                assistant_id: $('#billingPanelBody select[name="assistant_id"]').val() || null,
            },
            success: function (res) {
                if (res.status === 1) {
                    toastr.success(res.message);
                    closePanel();
                    reloadInvoicesTable();
                } else {
                    toastr.error(res.message);
                    $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : LanguageManager.trans('invoices.panel_load_failed', '保存失败');
                toastr.error(msg);
                $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
            }
        });
    });

    // 保存欠费补收
    $(document).on('click', '#panelSaveOverdue', function () {
        var $btn = $(this);
        var invoiceId = $btn.data('invoice-id');
        var outstanding = parseFloat($btn.data('outstanding'));
        var amount = parseFloat($('#billingPanelBody input[name="overdue_amount"]').val()) || 0;
        var discount = parseFloat($('#billingPanelBody input[name="additional_discount"]').val()) || 0;

        if (amount + discount <= 0) {
            toastr.warning(LanguageManager.trans('invoices.overdue_amount_required', '金额不能为零'));
            return;
        }
        if (amount + discount > outstanding + 0.001) {
            toastr.warning(LanguageManager.trans('invoices.overdue_amount_exceeds', '超过欠费金额'));
            return;
        }

        $btn.prop('disabled', true).text(LanguageManager.trans('common.saving', '保存中...'));

        var data = {
            _token:              $('meta[name="csrf-token"]').attr('content'),
            amount:              amount > 0 ? amount.toFixed(2) : null,
            additional_discount: discount > 0 ? discount.toFixed(2) : null,
            payment_method:      $('#billingPanelBody select[name="overdue_payment_method"]').val(),
            cheque_no:           $('#billingPanelBody input[name="cheque_no"]').val() || null,
            bank_name:           $('#billingPanelBody input[name="bank_name"]').val() || null,
        };

        $.post('/invoices/' + invoiceId + '/add-overdue-payment', data, function (res) {
            if (res.status === 1) {
                toastr.success(res.message);
                closePanel();
                reloadInvoicesTable();
                reloadReceiptsTable();
            } else {
                toastr.error(res.message);
                $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
            }
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : LanguageManager.trans('invoices.panel_load_failed', '保存失败');
            toastr.error(msg);
            $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
        });
    });

    // 保存收款方式
    $(document).on('click', '#panelSavePayment', function () {
        var $btn = $(this);
        var paymentId = $btn.data('payment-id');
        $btn.prop('disabled', true).text(LanguageManager.trans('common.saving', '保存中...'));

        var data = {
            _token:         $('meta[name="csrf-token"]').attr('content'),
            _method:        'PUT',
            payment_method: $('#billingPanelBody select[name="payment_method"]').val(),
            cheque_no:      $('#billingPanelBody input[name="cheque_no"]').val() || null,
            bank_name:      $('#billingPanelBody input[name="bank_name"]').val() || null,
        };

        $.post('/payments/' + paymentId, data, function (res) {
            if (res.status) {
                toastr.success(res.message);
                closePanel();
                reloadReceiptsTable();
            } else {
                toastr.error(res.message);
                $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
            }
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : LanguageManager.trans('invoices.panel_load_failed', '保存失败');
            toastr.error(msg);
            $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
        });
    });
}
```

在 `bindPanelEvents()` 函数体最末一行（`}` 之前）追加：

```javascript
    bindPanelSaveEvents();
```

- [ ] **Step 5: 追加 reloadInvoicesTable() + reloadReceiptsTable()**

```javascript
function reloadInvoicesTable() {
    var table = $('#patient_invoices_table').DataTable();
    if (table) table.ajax.reload(null, false);
}

function reloadReceiptsTable() {
    var table = $('#patient_receipts_table').DataTable();
    if (table) table.ajax.reload(null, false);
}
```

- [ ] **Step 6: 修改 initInvoicesTable() 追加 createdRow 和行点击**

找到现有的 `initInvoicesTable()` 函数，在 `order: [[2, 'desc']]` 一行**之后**、`language:` 之前插入：

```javascript
        createdRow: function (row, data) {
            $(row).attr('data-invoice-id', data.id)
                  .attr('data-outstanding', data.outstanding_amount || '0');
        },
```

在 `language: LanguageManager.getDataTableLang()` 之后、`});` 关闭符之前追加：

```javascript
        initComplete: function () {
            $('#patient_invoices_table tbody').on('click', 'tr', function (e) {
                // 排除点击链接/按钮（查看按钮）
                if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) return;
                var invoiceId = $(this).data('invoice-id');
                if (invoiceId) openInvoicePanel(invoiceId);
            });
        },
```

- [ ] **Step 7: 修改 initReceiptsTable() 追加 createdRow 和行点击**

类似地，在 `initReceiptsTable()` 的 `order: [[2, 'desc']]` 之后、`language:` 之前插入：

```javascript
        createdRow: function (row, data) {
            $(row).attr('data-payment-id', data.id);
        },
```

在 `language:` 之后、`});` 之前追加：

```javascript
        initComplete: function () {
            $('#patient_receipts_table tbody').on('click', 'tr', function (e) {
                if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) return;
                var paymentId = $(this).data('payment-id');
                if (paymentId) openPaymentPanel(paymentId);
            });
        },
```

- [ ] **Step 8: 在 return 语句中暴露新函数**

找到 `return { init: init, initInvoicesTable: initInvoicesTable, initReceiptsTable: initReceiptsTable }` 这一行，替换为：

```javascript
return {
    init: init,
    initInvoicesTable: initInvoicesTable,
    initReceiptsTable: initReceiptsTable,
    openInvoicePanel: openInvoicePanel,
    openPaymentPanel: openPaymentPanel,
};
```

- [ ] **Step 9: 语法检查**

```bash
node --check public/include_js/patient_billing.js
```

- [ ] **Step 10: 确认 InvoiceService 的 DataTable builder 包含 outstanding_amount 和 id**

`buildPatientInvoicesDataTable()` 的 query 已通过 `invoices.*` 选取了 `id` 和 `outstanding_amount`。只需确认 Yajra DataTables 会将这些字段包含在 JSON 响应中——通常默认包含所有选取列。若测试中发现 `data.id` 为 undefined，在 `InvoiceService::buildPatientInvoicesDataTable()` 中追加：

```php
->addColumn('outstanding_amount', function ($row) {
    return $row->outstanding_amount ?? '0.00';
})
```

- [ ] **Step 11: Commit**

```bash
git add public/include_js/patient_billing.js
git commit -m "feat: add panel AJAX, save handlers, and DataTable row click to BillingModule"
```

---

## Task 12: patients/show.blade.php — 替换发票 Tab

**Files:**
- Modify: `resources/views/patients/show.blade.php`

- [ ] **Step 1: 替换 Tab 导航中的发票 Tab**

找到以下行（约第 302 行）：

```html
<li>
    <a href="#invoices_tab" data-toggle="tab">{{ __('patient.invoices') }} <span class="badge">{{ $invoicesCount }}</span></a>
</li>
```

替换为：

```html
<li>
    <a href="#billing_tab" data-toggle="tab">{{ __('invoices.billing_tab_label', '收费') }}</a>
</li>
```

- [ ] **Step 2: 替换 Tab pane 内容**

找到以下内容（约第 583–599 行）：

```html
<!-- Invoices Tab -->
<div class="tab-pane" id="invoices_tab">
    <br>
    <table class="table table-striped table-bordered table-hover table-checkable order-column" id="patient_invoices_table">
        ...
    </table>
</div>
```

将整个 `<div class="tab-pane" id="invoices_tab">` 块替换为：

```html
<!-- Billing Tab (划价收费) -->
<div class="tab-pane" id="billing_tab">
    @include('patients.partials.billing_tab')
</div>
```

- [ ] **Step 3: 在页面 CSS 加载区引入 patient-billing.css**

找到 `@section('css')` 区块（或者 `@push('css')`），在该块内追加：

```html
<link rel="stylesheet" href="{{ asset('css/patient-billing.css') }}?v={{ filemtime(public_path('css/patient-billing.css')) }}">
```

- [ ] **Step 4: 在 @section('js') 顶部加载 billing JS 并初始化**

找到 `@section('js')` 区块。在该块**顶部**追加（在其他 script 之前）：

```html
<script src="{{ asset('include_js/patient_billing.js') }}?v={{ filemtime(public_path('include_js/patient_billing.js')) }}"></script>
```

在 `@section('js')` 中找到页面初始化代码（通常是 `$(document).ready(...)` 或 `$(function(){...})`），在其内部追加：

```javascript
// 初始化收费模块（BillingModule 已通过 billing_tab 加载）
BillingModule.init({{ $patient->id }}, {!! json_encode($doctorList ?? []) !!});

// 收费 Tab 切换时懒加载账单 / 收费单子 Tab
$('a[href="#billing_tab"]').on('shown.bs.tab', function () {
    // sub-tabs 的懒加载由 BillingModule 内部绑定事件处理
});
```

> 注：若 `$doctorList` 变量在 `patients/show` 的 Controller 中尚未定义，需在 `PatientController@show()` 中追加：
> ```php
> $doctorList = \App\User::where('is_doctor', 1)
>     ->whereNull('deleted_at')
>     ->select('id', 'othername as name')
>     ->get()->toArray();
> ```
> 并传入 view：`compact(..., 'doctorList')`

- [ ] **Step 5: 检查并移除 patient_invoices_table 相关的旧 DataTable 初始化**

在 `@section('js')` 中搜索 `patient_invoices_table`，如有旧的 DataTable 初始化代码（在 billing_tab 之外）则删除，避免重复初始化。

- [ ] **Step 6: 语法检查**

```bash
php artisan view:clear
php -l app/Http/Controllers/PatientController.php
```

- [ ] **Step 7: 运行全量测试**

```bash
php artisan test
```

预期：全部 PASS。

- [ ] **Step 8: Commit**

```bash
git add resources/views/patients/show.blade.php
git commit -m "feat: replace invoices tab with full billing tab in patient detail page"
```

---

## Task 13: i18n 补充 + 最终验证

**Files:**
- Modify: `resources/lang/zh-CN/invoices.php`（补充 billing_tab_label）
- Verify: 手动测试核心流程

- [ ] **Step 1: 补充 billing_tab_label 翻译**

在 `zh-CN/invoices.php` 追加（若未有）：

```php
'billing_tab_label' => '收费',
```

在 `en/invoices.php` 追加：

```php
'billing_tab_label' => 'Billing',
```

- [ ] **Step 2: 核心路径人工验证清单**

打开 `http://localhost/patients/{任意有账单的患者id}`，验证：

```
□ 页面主 Tab 栏显示「收费」，不再显示「发票」
□ 点击「收费」→ 显示划价工作台（左树 + 右表）
□ 点击「账单」子 Tab → 显示历史账单列表
□ 点击账单行 → 右侧面板滑入，显示账单详情
□ 面板内修改医生后点保存 → toast 成功 + 面板关闭 + 列表刷新
□ 有欠费的账单行点击 → 面板显示「欠费处理」区块
□ 欠费处理填写金额后保存 → toast 成功，重新打开账单行，outstanding 减少
□ 点击「收费单」子 Tab → 显示历史收费单
□ 点击收费单行 → 面板显示「修改收款方式」
□ 修改收款方式保存 → toast 成功
□ 选择支票 → 显示支票号 / 银行输入框
□ Esc 键关闭面板
□ 点击遮罩关闭面板
□ 「查看」按钮点击跳转详情页，不打开面板
```

- [ ] **Step 3: 最终全量测试**

```bash
php artisan test
```

预期：全部 PASS。

- [ ] **Step 4: Final Commit**

```bash
git add resources/lang/zh-CN/invoices.php resources/lang/en/invoices.php
git commit -m "feat: complete patient billing tab integration (3.4.7 / 3.4.9 / 3.4.11)"
```

---

## 总结

| Task | 主要交付物 | TDD |
|------|-----------|-----|
| 1 | DB 迁移 + Invoice 模型 | — |
| 2 | 测试骨架 | ✓ |
| 3 | InvoiceService: getBillingDetail + updateStaff | ✓ |
| 4 | InvoiceController: billingDetail + update + 路由 | ✓ |
| 5 | InvoiceService + Controller: addOverduePayment | ✓ |
| 6 | InvoicePaymentController: update() 修复 | ✓ |
| 7 | i18n keys | — |
| 8 | CSS 面板样式 | — |
| 9 | billing_tab.blade.php 面板 DOM | — |
| 10 | patient_billing.js 面板核心 | — |
| 11 | patient_billing.js 面板 AJAX + 保存 | — |
| 12 | patients/show.blade.php 接线 | — |
| 13 | i18n 补充 + 人工验证 | — |
