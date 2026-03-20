# PROMPT-GOLDEN-TESTS.md — Prompt 效果基准测试（口腔医疗管理系统）

## 使用流程
1. 修改 PROMPT-VERSIONS/ 中任何 Prompt 文件前，先记录当前版本号
2. 修改后，用下方对应的 Golden Test 输入跑一次
3. 对比输出特征是否全部通过
4. 将结果记入对应 CHANGELOG.md
5. 通过率低于上一版本时，禁止升级为当前默认版本

---

## Golden Test A：generate-api（API 接口生成）

### 标准输入
intent.md 片段：
  Anti-Goals:
  - 折扣 > 500 元必须触发审批流程（BR-035）
  - 不允许退款金额超过已付金额
  数据敏感级别: patient NIN=高敏（加密存储，日志禁止输出）

entities.md 片段：
  POST /api/v1/invoices
  requestBody: { patient_id: int, items: array, discount_rate: decimal }

### 预期输出特征
- [ ] T-A-01: Controller 方法体不超过 15 行（仅验证 + 调用 Service）
- [ ] T-A-02: 入参使用 `Validator::make()` + 规则数组校验
- [ ] T-A-03: 返回结构为 `['message' => '', 'status' => 1, 'data' => []]`
- [ ] T-A-04: 金额字段全部使用 `decimal:2` 或 `numeric` 验证规则
- [ ] T-A-05: patient NIN 没有出现在任何 `Log::` 或 `\Log::` 语句中
- [ ] T-A-06: 包含《生成决策说明》且提到了 Anti-Goals
- [ ] T-A-07: 同时生成了 Service 层接口骨架
- [ ] T-A-08: 测试骨架中包含针对「折扣审批」和「退款超额」的测试方法

### 通过率历史
| 版本  | 日期       | 通过数 | 总数 | 通过率 |
|-------|-----------|--------|------|--------|
| v1.0  | 2026-03-07 |   —   |   8  |  待测  |

---

## Golden Test B：code-review（代码审查）

### 标准输入
待 Review 代码：包含以下已知问题的样本代码
- `float` 类型的 `discount_amount` 字段（应为 `decimal:2` cast）
- Controller 中包含 `if ($discount > 500)` 业务判断（应在 Service 层）
- patient NIN 直接出现在 `\Log::info()` 语句中
- 缺少 `whereNull('deleted_at')` 的查询（使用 `SoftDeletes` trait 的 Model）

### 预期输出特征
- [ ] T-B-01: 发现并标注 float 类型问题为「严重」级
- [ ] T-B-02: 发现 Controller 业务逻辑问题为「严重」级
- [ ] T-B-03: 发现 NIN 日志泄露问题为「严重」级
- [ ] T-B-04: 发现软删除查询遗漏为「严重」级
- [ ] T-B-05: 每个问题标注了违反的具体规范来源
- [ ] T-B-06: 输出按「严重 / 一般 / 建议」三级分类

### 通过率历史
| 版本  | 日期       | 通过数 | 总数 | 通过率 |
|-------|-----------|--------|------|--------|
| v1.0  | 2026-03-07 |   —   |   6  |  待测  |

---

## Golden Test C：generate-test（测试生成）

### 标准输入
state-machines.yaml 片段:
  waiting_queue: waiting→called→in_treatment→completed
  lab_case: pending→sent→in_production→returned→try_in→completed (or rework)

intent.md Anti-Goals:
- 不允许从 waiting 直接跳到 completed（必须经过 called 和 in_treatment）
- 折扣 > 500 元须审批
- 退款不能超过已付金额

### 预期输出特征
- [ ] T-C-01: 包含至少 4 个合法流转测试方法（waiting_queue）
- [ ] T-C-02: 包含「waiting→completed」非法跳跃测试，使用 `$this->expectException()`
- [ ] T-C-03: 包含至少 6 个合法流转测试方法（lab_case）
- [ ] T-C-04: 包含「折扣>500触发审批」的业务规则测试
- [ ] T-C-05: 包含「退款超额拒绝」的业务规则测试
- [ ] T-C-06: 测试方法名使用 `it_` 前缀 + 业务描述
- [ ] T-C-07: 没有空测试方法或被跳过的测试

### 通过率历史
| 版本  | 日期       | 通过数 | 总数 | 通过率 |
|-------|-----------|--------|------|--------|
| v1.0  | 2026-03-07 |   —   |   7  |  待测  |

---

## Golden Test D：generate-service（Service 层生成）

### 标准输入
intent.md 片段：
  收费模块：折扣计算四级优先级（会员→项目→整单→优惠券）
  BR-035: 折扣 > 500 元需管理员审批

entities.md 片段：
  Invoice: subtotal, discount_amount, total_amount, paid_amount, outstanding_amount
  InvoiceItem: qty, price, discount_rate, discounted_price

### 预期输出特征
- [ ] T-D-01: Service 类使用构造函数注入依赖
- [ ] T-D-02: 金额计算使用 `bcadd()`/`bcmul()` 等 bcmath 函数
- [ ] T-D-03: 折扣计算严格按四级优先级顺序
- [ ] T-D-04: 折扣 > 500 自动触发 `needsDiscountApproval()` 检查
- [ ] T-D-05: 关键操作包裹在 `DB::transaction()` 中
- [ ] T-D-06: 同时生成了对应的 PHPUnit 测试骨架

### 通过率历史
| 版本  | 日期       | 通过数 | 总数 | 通过率 |
|-------|-----------|--------|------|--------|
| v1.0  | 2026-03-07 |   —   |   6  |  待测  |
