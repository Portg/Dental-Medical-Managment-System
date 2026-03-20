---
description: 按 TEST-SPEC.md 为指定代码生成 PHPUnit 测试，含 Anti-Goals 覆盖和状态流转测试
argument: 待测试的文件路径或类名
---

## 执行流程

### Step 1: 确定测试目标

- 参数是文件路径 → 读取该文件
- 参数是类名 → 通过 grep 定位文件
- 确定代码层级（Controller / Service / Model）

### Step 2: 加载测试规范

读取：
- `ai-dev-template/TEST-SPEC.md` — 测试规范
- `ai-dev-template/ai-spec/domain/intent.md` — Anti-Goals
- `ai-dev-template/ai-spec/domain/state-machines.yaml` — 状态机
- `ai-dev-template/ai-spec/engine/rule-engine.md` — 规则 Key
- `ai-dev-template/ai-spec/test/test-matrix.md` — 测试矩阵
- `ai-dev-template/ai-spec/test/mock-data.md` — Mock 数据规范

### Step 3: 分析需要覆盖的场景

根据代码层级确定测试类型：

**Controller → Feature 测试**
- 每个接口至少一个成功用例 + 一个验证失败用例
- 使用 `$this->actingAs($user)` 模拟认证
- API 测试验证响应结构

**Service → 单元测试**
- 每个公共方法有对应测试
- Anti-Goals 中每条规则有对应测试
- 使用 Mockery mock 外部依赖

**Model → 单元测试**
- 状态流转：所有合法路径 + 所有非法跳跃
- 金额计算：边界值覆盖（0, 0.01, 阈值-0.01, 阈值, 阈值+0.01）
- 编号生成：格式正确性 + 唯一性

### Step 4: 生成测试文件

测试文件放置规则：
- `tests/Feature/` — Controller / API 测试
- `tests/Unit/Models/` — Model 测试
- `tests/Unit/Services/` — Service 测试

测试命名规范：
```php
/** @test */
public function it_<动词>_when_<条件>()
{
    // Arrange - 准备测试数据（参考 mock-data.md）
    // Act - 执行被测方法
    // Assert - 验证结果
}
```

### Step 5: 生成 Anti-Goals 测试

扫描 intent.md 中与被测代码相关的 Anti-Goals，为每条生成测试：

```php
// AG-001: 折扣 > 500 元必须触发审批
/** @test */
public function it_requires_approval_when_discount_exceeds_threshold()

// AG-011: 候诊不能跳过状态
/** @test */
public function it_throws_when_skipping_queue_status()
```

### Step 6: 生成状态流转测试

如果被测代码涉及状态机，参考 state-machines.yaml 生成：

```php
// 合法路径 — 每条一个
/** @test */
public function it_can_transit_from_X_to_Y()

// 非法路径 — 每条一个
/** @test */
public function it_throws_when_transit_from_X_to_Z()
{
    $this->expectException(\InvalidArgumentException::class);
}
```

### Step 7: 验证

1. 运行 `php -l` 检查测试文件语法
2. 运行 `php artisan test --filter=<TestClass>` 执行测试
3. 如果测试框架报错（如缺少 Factory），自动补全

### Step 8: 输出

```
## 测试生成报告

**目标文件：** [被测文件]
**生成测试：** [测试文件路径]

### 测试方法清单
| # | 方法名 | 类型 | 覆盖的规则 |
|---|--------|------|-----------|
| 1 | it_xxx | 正常流程 | — |
| 2 | it_yyy | Anti-Goal | AG-001 |
| 3 | it_zzz | 状态流转 | waiting→called |

### Anti-Goals 覆盖
- AG-xxx: ✅ it_xxx()
- AG-yyy: ✅ it_yyy()

### 执行结果
[通过数/总数]
```
