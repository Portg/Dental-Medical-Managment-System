# WORKFLOW-KNOWHOW.md — 口腔医疗管理系统开发工作流知识库

## 项目技术栈约束

### 后端
- PHP 8.2+, Laravel 11.x
- MySQL 5.7+（135 张表，软删除 `deleted_at`）
- Eloquent ORM + Service 层模式
- 验证使用 `Validator::make()`，不用 FormRequest
- JSON 响应统一 `['message' => '', 'status' => 1/0, 'data' => []]`

### 前端
- Bootstrap + jQuery（不使用 Vue/React）
- Yajra DataTables 服务端分页
- FullCalendar 预约日历
- Select2 下拉选择
- Blade 模板 + 独立 CSS/JS 文件（禁止内联大段脚本）

### 国际化
- zh-CN 为主语言，en 为辅助语言
- 翻译文件在 `resources/lang/{locale}/`
- JS 端通过 `LanguageManager.trans()` 获取翻译

---

## 代码组织模式

### Controller → Service → Model 三层结构
```
Controller: 接收请求、验证参数、调用 Service、返回响应
Service:    业务逻辑编排、事务处理、复杂查询
Model:      数据定义、关系、访问器、作用域、状态常量
```

### 文件分离规范
| 类型 | 存放路径                    | 引入方式                                     |
|------|-----------------------------|----------------------------------------------|
| CSS  | `public/css/<page-name>.css`       | `<link>` 标签引入                            |
| JS   | `public/include_js/<page_name>.js` | `<script>` 标签 + filemtime 版本号            |

### 数据字典模式
- `DictItem` 表统一管理枚举值
- 通过 `DictItem::listByType($type)` 获取选项列表
- 通过 `DictItem::nameByCode($type, $code)` 获取显示名称（缓存 3600s）

---

## 核心业务流程

### 1. 患者就诊流程
```
预约(Appointment) → 签到(WaitingQueue:waiting) → 叫号(called)
→ 就诊(in_treatment) → 完成治疗(completed)
→ 创建病历(MedicalCase) → 开处方/治疗计划 → 收费(Invoice)
```

### 2. 收费折扣审批流程
```
创建账单 → 计算折扣（会员→项目→整单→优惠券 四级优先级）
→ 折扣 > 500元？ → 是：提交审批(pending) → 审批通过/拒绝
                   → 否：直接收款
→ 收款 → 欠费挂账(可选) → 退款(> 100元需审批)
```

### 3. 技工单流程
```
创建(pending) → 送出(sent) → 制作中(in_production)
→ 返回(returned) → 试戴(try_in) → 完成(completed)
                                   ↘ 返工(rework) → 送出
```

### 4. 病历修改合规流程
```
病历创建(open) → 签名锁定(locked_at)
→ 需修改？ → 创建修改申请(Amendment:pending)
→ 审批通过(approved) → 自动应用修改 + version_number++
→ 审批拒绝(rejected) → 记录原因
```

---

## 常见陷阱

### 1. 软删除
- 所有查询必须包含 `whereNull('deleted_at')` 或使用 `SoftDeletes` trait
- 关联查询注意级联软删除

### 2. 金额计算
- 所有金额字段使用 `decimal:2` cast
- 不要用浮点运算，使用 `bcadd()` / `bcsub()` / `bcmul()`
- Invoice 的 `outstanding_amount` 在 `saving` 事件中自动计算

### 3. 状态同步
- WaitingQueue 状态变更会自动同步 Appointment 状态
- Invoice 的 `payment_status` 在保存时根据 `paid_amount` 自动计算
- 不要手动设置自动计算的字段

### 4. 缓存失效
- `DictItem` 修改后会自动清理缓存
- `MedicalService` 修改后会清理 `billing_service_category_tree` 缓存
- 手动修改数据库时注意清理 `php artisan cache:clear`

---

## 编码规范速查

### 验证模式
```php
$validator = Validator::make($request->all(), [
    'field' => 'required|string|max:255',
]);
if ($validator->fails()) {
    return response()->json(['message' => $validator->errors()->first(), 'status' => 0]);
}
```

### 编号生成模式
```php
// 所有编号遵循：前缀 + 日期 + 序号
// 例：LC20260307-0001（技工单）、RX20260307-0001（处方）
$prefix . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT)
```

### DataTable 返回模式
```php
return DataTables::of($query)
    ->addColumn('action', function ($row) { ... })
    ->rawColumns(['action'])
    ->make(true);
```
