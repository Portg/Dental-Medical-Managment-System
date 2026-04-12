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

- 面板宽度：`280px`，`position: fixed`，右侧贴边
- 背景遮罩（overlay）：点击关闭面板
- 面板独立于 DataTable，不影响主列表滚动
- 账单面板和收费单面板为同一个 DOM 节点，内容按触发类型动态渲染

---

## 架构

### 改动文件（6 个）

| 文件 | 变更类型 | 说明 |
|------|---------|------|
| `resources/views/patients/show.blade.php` | 修改 | 移除「发票」Tab；新增「收费」Tab；`@include billing_tab`；加载 `patient_billing.js` 和 `patient_billing.css` |
| `resources/views/patients/partials/billing_tab.blade.php` | 修改 | 账单 / 收费单子 Tab 行加 `data-*` 属性和点击回调；新增右侧面板 HTML（overlay + panel DOM） |
| `public/include_js/patient_billing.js` | 修改 | 新增面板 open / close 逻辑；新增 3 个 AJAX 方法 |
| `public/css/patient_billing.css` | **新建** | 面板滑入动画、overlay、面板内部样式 |
| `app/Http/Controllers/InvoiceController.php` | 修改 | 实现 `update()`（人员字段）；新增 `addOverduePayment()` |
| `database/migrations/<timestamp>_add_staff_to_invoices_table.php` | **新建** | invoices 表新增 `doctor_id` / `nurse_id` / `assistant_id` 可空外键；`<timestamp>` 由 `php artisan make:migration` 自动生成 |

### 不改动

- `routes/web.php`：3.4.11 使用已有 `PUT /payments/{id}`（`InvoicePaymentController@update`）
- `InvoicePaymentController.php`：逻辑完整，直接复用

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
→ GET /invoice-amount/{id}          ← 复用已有接口
→ 渲染面板：基本信息 + 人员下拉 + 欠费区块
```

**收费单行点击：**
```
BillingModule.openPaymentPanel(paymentId)
→ GET /payments/{id}/edit           ← InvoicePaymentController@edit 已存在
→ 渲染面板：收款方式选择 + 金额（只读）
```

### 3.4.9 修改人员

```
PATCH /invoices/{id}
Request:  { doctor_id, nurse_id, assistant_id }
Response: { status: 1, message: '保存成功' }
→ 面板关闭 + 账单子 Tab 刷新当前行
```

### 3.4.7 欠费补收与再优惠

```
POST /invoices/{id}/add-overdue-payment
Request:  { amount, payment_method, additional_discount?,
            payment_date?, cheque_no?, bank_name?,
            insurance_company_id?, self_account_id? }
服务端:   若 additional_discount > 0：
            invoices.discount_amount += additional_discount
            invoices.total_amount -= additional_discount（重新计算 outstanding）
          新建 InvoicePayment 记录（amount 为实际补收金额）
          invoices.paid_amount += amount
          outstanding_amount <= 0 → payment_status = 'paid'
          否则 → payment_status = 'partial'
Response: { status: 1, message: '补收成功', data: { new_outstanding } }
→ 面板关闭 + 账单子 Tab 刷新
```

前端面板新增「再优惠金额」输入框（选填），提示「填写后将从欠费中直接减免，无需实际收款」。

### 3.4.11 修改收款方式

```
PUT /payments/{id}                  ← 已有路由，无需新增
Request:  { payment_method, cheque_no?, bank_name?,
            insurance_company_id?, self_account_id? }
Response: { status: 1, message: '修改成功' }
→ 面板关闭 + 收费单子 Tab 刷新
```

---

## 后端新增路由

仅新增 1 条：

```php
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
| 发票已全额支付仍尝试补收 | 检查 `outstanding_amount`，返回错误 |
| 无 `edit-invoices` 权限 | 中间件拦截，返回 403 |
| 修改收款方式支票字段缺失 | 复用 `InvoicePaymentController@update` 现有校验 |

---

## 国际化

新增用户可见文本均使用 `__()` / `LanguageManager.trans()`，需同步更新 `resources/lang/zh-CN/` 和 `en/` 对应语言文件。

主要新增 key：

```
billing.panel_invoice_detail   账单详情
billing.modify_staff           修改人员
billing.overdue_payment        欠费处理
billing.supplement_amount      补收金额
billing.modify_payment_method  修改收款方式
billing.save_success           保存成功
billing.load_failed            加载失败，请重试
billing.amount_exceeds_overdue 补收金额不能超过欠费金额
```

---

## 不在本次范围内

- 补录时间是否回写病历 / 处置就诊时间（涉及审计数据，需单独产品设计）
- 整单退款流程
- 发票打印样式调整
