# TEST-SPEC.md — AI 生成代码测试规范（口腔医疗管理系统）
# 演进式设计的质量守门

## 强制原则
1. **测试与代码同步生成**：Coding Agent 收到任务时，测试是任务的一部分
2. **测试先于代码检视**：Review Agent 必须先看测试，再看实现
3. **Anti-Goals 必须有测试覆盖**：每条 Anti-Goal 对应至少一个测试方法

---

## 分层测试强制要求

| 代码层       | 测试类型       | 最低覆盖率  | 框架              | 特殊要求                                              |
|-------------|---------------|------------|-------------------|-------------------------------------------------------|
| Controller  | Feature 测试   | 80%        | PHPUnit + Laravel | 每个接口至少一个成功用例 + 一个验证失败用例              |
| Service     | 单元测试       | 90%        | PHPUnit + Mockery | Anti-Goals 中每条规则必须有对应测试用例                  |
| Model       | 单元测试       | 核心方法100% | PHPUnit          | 状态流转、金额计算、编号生成                             |
| 状态流转     | 状态边界值测试 | 100%       | PHPUnit           | 合法流转 + 所有非法跳跃（必须抛出明确异常）              |
| 规则引擎     | 规则单元测试   | 100%       | PHPUnit           | rule-engine.md 每个 Rule Key 对应一个测试               |

---

## 状态机测试规范（最严格）

### 必须覆盖的用例类型

#### 合法流转测试（每条合法路径一个用例）
```php
/** @test */
public function it_can_transit_waiting_queue_from_waiting_to_called()
{
    $queue = WaitingQueue::factory()->create(['status' => WaitingQueue::STATUS_WAITING]);
    $queue->callPatient($calledBy, $chairId);
    $this->assertEquals(WaitingQueue::STATUS_CALLED, $queue->fresh()->status);
}

/** @test */
public function it_can_transit_lab_case_from_pending_to_sent()
{
    $labCase = LabCase::factory()->create(['status' => LabCase::STATUS_PENDING]);
    // ... transition logic
    $this->assertEquals(LabCase::STATUS_SENT, $labCase->fresh()->status);
}
```

#### 非法跳跃测试（每条非法路径一个用例，必须抛出异常）
```php
/** @test */
public function it_throws_when_transit_waiting_queue_from_waiting_to_completed()
{
    $queue = WaitingQueue::factory()->create(['status' => WaitingQueue::STATUS_WAITING]);
    $this->expectException(\InvalidArgumentException::class);
    $queue->completeTreatment(); // 不能跳过 called 和 in_treatment
}
```

#### Anti-Goals 测试
```php
// Anti-Goal: 折扣 > 500 元必须触发审批
/** @test */
public function it_requires_approval_when_discount_exceeds_500()
{
    $invoice = Invoice::factory()->create();
    $invoice->applyDiscounts(['order_discount_amount' => 600]);
    $this->assertEquals(Invoice::DISCOUNT_PENDING, $invoice->discount_approval_status);
}

// Anti-Goal: 病历锁定后不允许直接修改
/** @test */
public function it_prevents_direct_modification_after_locking()
{
    $case = MedicalCase::factory()->create();
    $case->lock();
    $this->assertFalse($case->canModifyWithoutApproval());
}
```

---

## `@AiGenerated` 注释规范

当 AI 生成的代码存在以下情况时，必须标注：
1. 逻辑复杂但未经充分人工审查
2. 性能未经过基准测试
3. 依赖了 Mock 数据的临时实现
4. AI 推理链记录显示「不确定」的部分

```php
// @AiGenerated
// reason: 折扣叠加逻辑待人工复核
// generatedAt: 2026-03-07
// reviewBy: 2026-Q2
// scratchpadRef: 2026-03-07_收费模块
public function calculateDiscounts($patient, $coupon, $orderDiscountRate, $orderDiscountFixed)
{
    // ...
}
```

---

## 测试文件组织

```
tests/
├── Feature/
│   ├── AppointmentControllerTest.php
│   ├── InvoiceControllerTest.php
│   ├── MedicalCaseControllerTest.php
│   ├── LabCaseControllerTest.php
│   └── Api/V1/
│       ├── PatientApiTest.php
│       ├── AppointmentApiTest.php
│       └── InvoiceApiTest.php
├── Unit/
│   ├── Models/
│   │   ├── AppointmentTest.php
│   │   ├── InvoiceTest.php
│   │   ├── MedicalCaseTest.php
│   │   ├── WaitingQueueTest.php
│   │   └── LabCaseTest.php
│   └── Services/
│       ├── AppointmentServiceTest.php
│       ├── InvoiceServiceTest.php
│       ├── MedicalCaseServiceTest.php
│       └── LabCaseServiceTest.php
```

---

## 测试命名规范

- 方法名使用 `it_` 前缀 + 业务描述（snake_case）
- 或使用 `@test` 注解 + `test` 前缀
- 复杂测试添加中文注释说明业务语义

```php
/** @test */
public function it_auto_calculates_outstanding_amount_on_payment()
{
    // 验证：付款后 outstanding_amount = total_amount - paid_amount
}

/** @test */
public function it_rejects_refund_exceeding_paid_amount()
{
    // Anti-Goal: 退款金额不能超过已付金额
}
```

---

## 测试数据规范

- 使用 Laravel Factory 生成测试数据
- 金额测试必须覆盖边界值：0, 0.01, 500 (审批阈值), 500.01, 99999.99
- 状态测试必须覆盖所有合法起始状态
- 日期测试必须覆盖：过去、今天、未来、空值
