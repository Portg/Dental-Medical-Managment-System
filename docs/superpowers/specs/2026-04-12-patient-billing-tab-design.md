# 患者详情「收费」Tab 改造设计

**日期**：2026-04-12  
**状态**：待实现  
**范围**：患者详情页收费入口整合 + 3 项缺失功能补齐（对标参考系统 3.4.1–3.4.11）

---

## 背景

现有系统的患者详情页（`patients/show`）有一个「发票」Tab，仅展示账单列表，不具备完整的划价收费能力。完整的 `billing_tab.blade.php` 和 `patient_billing.js` 已存在，但未接入患者详情页。

参考系统（kqyun.com 文档 3.4.1–3.4.11）将划价、账单、收费单三个子功能集中在患者详情内，形成完整收费工作台。本次改造对标该设计，同时补齐 3 项现有实现缺失的功能。

---

## 目标

1. 将患者详情的「发票」Tab 替换为完整的「收费」Tab（含三个子 Tab：划价 / 账单 / 收费单）
2. 补齐 3 项缺失功能：
   - **3.4.7** 欠费账单的后续补收与再优惠
   - **3.4.9** 账单详情里修改医生 / 护士 / 助理
   - **3.4.11** 收费单里修改收款方式

---

## Tab 结构

### 改动前

```
基本信息 | 牙位图 | 预约 | 病历 | 图像 | 发票 | 回访
```

### 改动后

```
基本信息 | 牙位图 | 预约 | 病历 | 图像 | 收费 | 回访
                                           ↓
                              子 Tab：划价 | 账单 | 收费单
```

「发票」Tab 移除，其账单列表职责由「收费 → 账单」子 Tab 承接。

---

## UI 设计

### 划价子 Tab（主工作台）

沿用现有 `billing_tab.blade.php` 的划价布局：

- **左侧**（`col-md-3`）：收费项目分类树，含搜索框，点击项目添加到右侧明细
- **右侧**（`col-md-9`）：收费明细表
  - 列：序号 / 项目 / 单位 / 单价 / 数量 / 合计 / 折扣% / 折后价 / 实收 / 欠费 / 医生 / 删除
  - 末行汇总：原价 / 折后价 / 实收 / 欠费 / 整单折扣输入
- **收款方式区块**：支持现金 / 微信 / 支付宝 / 银行卡 / 保险 / 支票 / 储值 / 自费账户，最多可添加多条
- **补录**：复选框勾选后解锁日期选择器
- **操作按钮**：前台结账 / 直接收费 / 收费并打印

折扣超过审批阈值时禁用收费按钮，展示审批警告（现有逻辑保留）。

### 账单子 Tab

DataTable 展示患者历史账单，新增行点击交互：

- 点击任意账单行 → 右侧详情面板滑入
- 面板内容：账单基本信息 + **修改人员**区块 + **欠费处理**区块（仅 `outstanding_amount > 0` 时显示）

### 收费单子 Tab

DataTable 展示历史收费单，新增行点击交互：

- 点击任意收费单行 → 右侧详情面板滑入
- 面板内容：收款方式 + 金额（金额只读）+ **修改收款方式**区块

### 右侧滑出面板

用纯 CSS `transform: translateX` 实现滑入动画，无需 Bootstrap 5：

- 面板宽度：桌面 `420px`，窄屏 `100vw`，`position: fixed`，右侧贴边
- 背景遮罩（overlay）：点击关闭面板
- 面板独立于 DataTable，不影响主列表滚动
- 账单面板和收费单面板为同一个 DOM 节点，内容按触发类型动态渲染
- 支持 `Esc` 关闭；打开后焦点进入面板首个可操作控件，关闭后焦点回到触发行
- 保存按钮提交中展示 loading 并禁用，响应返回后恢复
- DataTable 行点击打开面板，但「查看」按钮点击只跳转详情页，不触发行点击

---

## 架构

### 改动文件

| 文件 | 变更类型 | 说明 |
|------|---------|------|
| `resources/views/patients/show.blade.php` | 修改 | 将原 `invoices_tab` 整段替换为「收费」Tab，不保留旧 `patient_invoices_table`；`@include('patients.partials.billing_tab')`；加载 `patient_billing.js` 和 `patient-billing.css` |
| `resources/views/patients/partials/billing_tab.blade.php` | 修改 | 账单 / 收费单子 Tab 行加 `data-*` 属性和点击回调；新增右侧面板 HTML（overlay + panel DOM） |
| `public/include_js/patient_billing.js` | 修改 | 新增面板 open / close 逻辑；新增行点击处理；新增账单详情、人员更新、欠费补收、收费单更新 AJAX 方法 |
| `public/css/patient-billing.css` | 修改 | 在既有文件中追加面板滑入动画、overlay、面板内部样式；不新建 `patient_billing.css` |
| `app/Http/Controllers/InvoiceController.php` | 修改 | 实现 `update()`（人员字段）；新增 `billingDetail()`；新增 `addOverduePayment()` |
| `app/Services/InvoiceService.php` | 修改 | 新增账单详情数据组装、人员字段更新、欠费补收服务方法；DataTable 增加行级 `data-*` 所需字段 |
| `app/Http/Controllers/InvoicePaymentController.php` | 修改 | 调整 `update()` 支持仅修改收款方式及附属字段；不再假设现有实现可直接复用 |
| `app/Services/InvoicePaymentService.php` | 修改 | `updatePayment()` 接收并保存 `cheque_no` / `bank_name` / `insurance_company_id` / `self_account_id`；必要时保留原金额与日期 |
| `app/Invoice.php` | 修改 | `$fillable` 增加 `doctor_id` / `nurse_id` / `assistant_id`；增加对应 `belongsTo(User::class)` 关系 |
| `database/migrations/<timestamp>_add_staff_to_invoices_table.php` | **新建** | invoices 表新增 `doctor_id` / `nurse_id` / `assistant_id` 可空外键；`<timestamp>` 由 `php artisan make:migration` 自动生成 |
| `resources/lang/zh-CN/invoices.php` | 修改 | 新增本功能文案 |
| `resources/lang/en/invoices.php` | 修改 | 新增本功能文案 |
| `routes/web.php` | 修改 | 新增账单详情和欠费补收路由；保留已有 `PUT /payments/{id}` |

### 不改动

- 不新增前端依赖，不引入 Bootstrap 5
- 不改变账单详情页 `invoices/{id}` 原有入口
- 不调整发票打印模板

---

## 现有代码差异与约束

- 当前患者详情页已有 `#invoices_tab` 和 `#patient_invoices_table`；接入收费 Tab 时必须替换旧节点，不能让旧表格和 `billing_tab.blade.php` 里的同名表格并存。
- 当前样式文件名是 `public/css/patient-billing.css`，不是 `patient_billing.css`。
- 当前 `InvoicePaymentController@update()` 要求 `amount` / `payment_date` / `payment_method`，且只传这 3 个字段给 service；实现 3.4.11 前必须调整该方法，否则收款方式附属字段不会保存。
- 当前 `GET /invoice-amount/{id}` 只适合收款余额场景，不足以渲染账单详情面板；本次新增 `GET /invoices/{id}/billing-detail`。
- 当前 `Invoice` 模型保存时会自动重算 `outstanding_amount` 和 `payment_status`；欠费补收不要手写状态分支，保存账单后让模型规则统一处理。

---

## 支付方式编码

前端和后端必须使用同一套编码。以 `InvoicePaymentService::PAYMENT_METHODS` 为准，前端选项同步改为：

| 中文 | code |
|------|------|
| 现金 | `Cash` |
| 微信 | `WeChat` |
| 支付宝 | `Alipay` |
| 银行卡 | `BankCard` |
| 保险 | `Insurance` |
| 支票 | `Cheque` |
| 储值 | `StoredValue` |
| 自费账户 | `Self Account` |

实现时把现有前端 `WechatPay` 改为 `WeChat`，`SelfAccount` 改为 `Self Account`，并同步更新判断附属字段显示的 JS 条件。

---

## 数据流

### 现有划价提交流程（保持不变）

```
用户选项目 → addBillingItem() → recalculateTotals()
→ submitBilling(mode) → POST /billing/create
→ 成功后 resetBillingForm() + 刷新账单 / 收费单子 Tab
```

### 面板触发流程

**账单行点击：**
```
BillingModule.openInvoicePanel(invoiceId)
→ GET /invoices/{id}/billing-detail
→ 渲染面板：基本信息 + 人员下拉 + 欠费区块
```

**收费单行点击：**
```
BillingModule.openPaymentPanel(paymentId)
→ GET /payments/{id}/edit           ← InvoicePaymentController@edit 已存在
→ 渲染面板：收款方式选择 + 金额（只读）
```

---

## 接口契约

### 账单详情面板

```
GET /invoices/{id}/billing-detail
Response:
{
  status: 1,
  data: {
    id,
    invoice_no,
    invoice_date,
    total_amount,
    paid_amount,
    outstanding_amount,
    payment_status,
    doctor_id,
    nurse_id,
    assistant_id,
    doctors: [{ id, name }],
    nurses: [{ id, name }],
    assistants: [{ id, name }]
  }
}
```

用途：只供患者详情收费 Tab 的账单右侧面板使用，不替代 `GET /invoice-amount/{id}`。

### 3.4.9 修改人员

```
PATCH /invoices/{id}
Request:  { doctor_id, nurse_id, assistant_id }
Response: { status: 1, message: '保存成功' }
→ 面板关闭 + 账单子 Tab 刷新当前行
```

后端要求：

- `doctor_id` / `nurse_id` / `assistant_id` 均允许为空
- 非空时使用 `exists:users,id` 校验
- 只更新这 3 个字段，不改动账单金额、状态、支付记录

### 3.4.7 欠费补收与再优惠

```
POST /invoices/{id}/add-overdue-payment
Request:  { amount, payment_method, additional_discount?,
            payment_date?, cheque_no?, bank_name?,
            insurance_company_id?, self_account_id? }
服务端:   若 additional_discount > 0：
            用 bcmath 增加 invoices.discount_amount
            用 bcmath 减少 invoices.total_amount
          若 amount > 0：
            复用 InvoicePaymentService::processMixedPayment()
            新建 InvoicePayment 记录（amount 为实际补收金额）
            更新 invoices.paid_amount
          保存 invoice，由 Invoice 模型自动重算 outstanding_amount / payment_status
Response: { status: 1, message: '补收成功', data: { new_outstanding } }
→ 面板关闭 + 账单子 Tab 刷新
```

前端面板新增「再优惠金额」输入框（选填），提示「填写后将从欠费中直接减免，无需实际收款」。

后端要求：

- `amount` 和 `additional_discount` 至少一个大于 0
- `amount + additional_discount` 不能超过当前 `outstanding_amount`
- 金额计算统一使用 `bcadd` / `bcsub` / `bccomp`
- `payment_method = StoredValue` 时继续走储值余额校验和会员流水逻辑
- 折扣审批未通过且账单不能收款时，禁止补收；仅再优惠也需要明确返回错误，避免绕过折扣审批

### 3.4.11 修改收款方式

```
PUT /payments/{id}
Request:  { payment_method, cheque_no?, bank_name?,
            insurance_company_id?, self_account_id? }
Response: { status: 1, message: '修改成功' }
→ 面板关闭 + 收费单子 Tab 刷新
```

后端要求：

- 保留原支付记录的 `amount` 和 `payment_date`，除非 request 显式传入且通过校验
- 支票必须校验 `cheque_no` / `bank_name`
- 保险必须校验 `insurance_company_id`
- 自费账户必须校验 `self_account_id`
- service 层必须把附属字段纳入 update whitelist

---

## 后端新增路由

新增 2 条：

```php
Route::get('invoices/{id}/billing-detail', 'InvoiceController@billingDetail');
Route::post('invoices/{id}/add-overdue-payment', 'InvoiceController@addOverduePayment');
```

---

## 数据库迁移

```php
// invoices 表新增人员字段
$table->unsignedBigInteger('doctor_id')->nullable();
$table->unsignedBigInteger('nurse_id')->nullable();
$table->unsignedBigInteger('assistant_id')->nullable();

$table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
$table->foreign('nurse_id')->references('id')->on('users')->nullOnDelete();
$table->foreign('assistant_id')->references('id')->on('users')->nullOnDelete();
```

迁移后同步更新：

- `app/Invoice.php` 的 `$fillable`
- `Invoice` 到 `User` 的 `doctor` / `nurse` / `assistant` 关系
- 账单详情接口返回当前人员字段和下拉选项
- `InvoiceController@update()` 的字段白名单和校验规则

---

## 错误处理

### 前端

| 场景 | 处理 |
|------|------|
| 面板打开时网络失败 | Toast 提示「加载失败，请重试」，面板不展开 |
| 补收金额 > 欠费金额 | 前端校验，输入框红边 + 提示「补收金额不能超过欠费金额」 |
| 多次点击「保存」 | 提交后立即 disabled，响应返回后恢复 |
| 面板打开时点击 overlay | 面板关闭，不提交 |

### 后端

| 场景 | 处理 |
|------|------|
| doctor_id 不存在 | `exists:users,id` 校验，返回 `{ status: 0 }` |
| 欠费补收金额 ≤ 0 | `min:0.01` 校验 |
| 补收 + 再优惠超过欠费 | 返回 `{ status: 0, message }`，不写入任何记录 |
| 发票已全额支付仍尝试补收 | 检查 `outstanding_amount`，返回错误 |
| 无 `edit-invoices` 权限 | 中间件拦截，返回 403 |
| 修改收款方式支票字段缺失 | `InvoicePaymentController@update` 返回校验错误 |

### 空态与部分状态

| 场景 | 用户看到 |
|------|----------|
| 账单子 Tab 无数据 | 「暂无账单，可先在划价中新增收费项目」 |
| 收费单子 Tab 无数据 | 「暂无收费记录」 |
| 账单已结清 | 面板显示「该账单已结清」，隐藏欠费处理表单 |
| 保险 / 自费账户搜索失败 | 保持面板打开，字段下方显示「选项加载失败，请重试」 |
| 点击行内「查看」按钮 | 打开原账单详情页，不打开右侧面板 |

---

## 国际化

新增用户可见文本均使用 `__()` / `LanguageManager.trans()`，需同步更新 `resources/lang/zh-CN/invoices.php` 和 `resources/lang/en/invoices.php`。

主要新增 key：

```
invoices.panel_invoice_detail        账单详情
invoices.modify_staff                修改人员
invoices.overdue_payment             欠费处理
invoices.supplement_amount           补收金额
invoices.additional_discount         再优惠金额
invoices.modify_payment_method       修改收款方式
invoices.save_success                保存成功
invoices.load_failed                 加载失败，请重试
invoices.amount_exceeds_overdue      补收金额不能超过欠费金额
invoices.adjustment_exceeds_overdue  补收与再优惠合计不能超过欠费金额
invoices.no_patient_invoices         暂无账单，可先在划价中新增收费项目
invoices.no_patient_receipts         暂无收费记录
invoices.invoice_settled             该账单已结清
invoices.option_load_failed          选项加载失败，请重试
```

---

## 不在本次范围内

- 补录时间是否回写病历 / 处置就诊时间（涉及审计数据，需单独产品设计）
- 整单退款流程
- 发票打印样式调整
