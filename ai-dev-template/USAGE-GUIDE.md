# AI 开发规范使用指南 — 口腔医疗管理系统

## 快速上手：我该用什么命令？

根据你当前在做的事情，选择对应的命令：

| 我正在做...            | 使用命令                     | 说明                                    |
|-----------------------|-----------------------------|-----------------------------------------|
| 新需求，还不确定细节    | `/spec-discovery <需求>`    | 结构化追问，生成 intent.md               |
| 确认需求，准备动手      | `/arch-check <功能>`        | 先做架构合规检查，再给实现方案            |
| 开始写代码             | `/implement <任务>`         | 带架构约束 + Anti-Goals 检查的实现流程    |
| 代码写完了，想检查质量   | `/vibe-check`              | 六关验收门禁，快速发现问题                |
| 想要详细的代码审查      | `/code-review <文件>`       | 三级问题报告（严重/一般/建议）            |
| 需要补测试             | `/gen-test <文件>`          | 按 TEST-SPEC 生成，含 Anti-Goals 覆盖    |
| 遇到了 Bug             | `/fix-bug <描述>`           | 根因分析 + 推理链 + Anti-Goals 回归检查   |
| 会话结束，记录进展      | `/scratchpad`              | 记录决策、推理链、遗留问题到 SCRATCHPAD   |

---

## 一、需求阶段：/spec-discovery

### 什么时候用？
- 拿到新需求，还不清楚边界和细节
- 想在写代码之前把业务规则理清

### 用法
```
/spec-discovery 添加患者预约短信提醒功能
```

### 会发生什么？
AI 按 3 个阶段和你对话：

**Phase 1 — 追问**（约 10 个问题）
```
1. 这个功能面向哪些角色？
2. 正常流程是什么？
3. 异常流程有哪些？
4. 涉及哪些现有实体？
...
```
你逐一回答，AI 整理出需求边界。

**Phase 2 — 破坏者**（AI 自动分析）
AI 扮演恶意用户，思考如何破坏这个功能，输出 Anti-Goals 建议：
```
- AG-021: 不允许对已取消的预约发送提醒
- AG-022: 短信发送频率不超过每小时 1 次/患者
```

**Phase 3 — 审计员**（AI 自动分析）
AI 审查数据安全：
```
- 短信内容不包含患者完整姓名，使用脱敏格式
- 发送日志不记录手机号全文
```

**最终输出** → intent.md 更新建议，你审定后即为后续开发的最高优先级。

---

## 二、设计阶段：/arch-check

### 什么时候用？
- 确认需求后，开始设计方案
- 不确定代码应该放在哪里

### 用法
```
/arch-check 添加患者预约短信提醒功能
```

### 会发生什么？
AI 设计方案后自动执行 6 项合规检查：

```
| # | 规则           | 结果 | 说明                                    |
|---|---------------|------|-----------------------------------------|
| 1 | 模块依赖方向   | ✅   | app/Services → 方向正确                  |
| 2 | 单一职责       | ✅   | SmsService 只负责短信发送                |
| 3 | 技术选型       | ✅   | 使用项目已有的 queue 机制                 |
| 4 | 跨模块通信     | ✅   | 通过 Event 解耦                          |
| 5 | Anti-Goals     | ✅   | 无违反 intent.md 中的条目                |
| 6 | 状态流转       | ✅   | 只在 scheduled 状态发送提醒              |
```

不合规的方案 AI 会自动换掉，你看到的一定是合规的。

---

## 三、实现阶段：/implement

### 什么时候用？
- 设计确认，准备写代码
- 任何需要写代码的任务

### 用法
```
/implement 在 AppointmentService 中添加短信提醒定时任务
```

### 与之前有什么不同？

新增了 3 个阶段：

**Phase 1.5 — 上下文切片加载**
根据任务类型自动加载最小必要上下文：
```
预约相关 → 切片 A（只加载 Appointment, WaitingQueue, Chair, DoctorSchedule）
收费相关 → 切片 B（只加载 Invoice, InvoiceItem, Refund, Coupon）
...
```

**Phase 4 — VIBE-CHECKLIST 自检**
代码写完后，自动对照六关门禁快速自检：
```
✅ 第一关：意图对齐 — Anti-Goals 未违反
✅ 第二关：上下文遗漏 — 状态机完整
✅ 第三关：AI 幻觉 — 无发明字段
✅ 第四关：医疗场景 — bcmath + 脱敏
✅ 第五关：代码质量 — 无 dd() 残留
✅ 第六关：重构信号 — 方法 < 30 行
```

**Phase 5 — SCRATCHPAD 记录**（复杂任务可选）
记录关键决策和推理链。

### 实现中的 Anti-Goals 检查

代码中遇到以下场景时，会自动提醒你遵守规则：

| 场景 | 自动检查 |
|------|---------|
| 金额计算 | 必须用 `bcadd()`/`bcmul()` 等，不用浮点 (AG-005) |
| 阈值判断 | 引用 rule-engine.md 的 Key，不硬编码 (AG-020) |
| 状态变更 | 符合 state-machines.yaml 白名单 |
| 日志输出 | NIN 不出现在日志中 (AG-015) |
| 复杂逻辑 | 标注 `// @AiGenerated` |

---

## 四、检查阶段：/vibe-check 和 /code-review

### /vibe-check — 快速检查

适合代码写完后快速过一遍，1 分钟出结果。

```
/vibe-check                          # 检查未提交的改动
/vibe-check app/Services/InvoiceService.php   # 检查指定文件
/vibe-check HEAD~3                   # 检查最近 3 次提交
```

输出示例：
```
| 关卡               | 结果 | 发现问题数 |
|-------------------|------|-----------|
| 第一关：意图对齐    | ✅   | 0         |
| 第二关：上下文遗漏  | ✅   | 0         |
| 第三关：AI 幻觉     | ✅   | 0         |
| 第四关：医疗场景    | ❌   | 1         |  ← 发现 float 运算
| 第五关：代码质量    | ✅   | 0         |
| 第六关：重构信号    | ⚠️   | 1         |  ← 方法 35 行

严重：1 / 一般：0 / 提示：1
```

### /code-review — 详细审查

适合重要功能完成后的深度审查，会逐条对照 Anti-Goals。

```
/code-review app/Services/InvoiceService.php
/code-review 收费模块折扣计算逻辑
/code-review 最近修改的文件
```

输出更详细的三级报告，包含：
- 严重问题（阻断合并）— 引用具体的 AG-xxx 编号
- 一般问题（需修复）— 引用 CLAUDE.md 规范
- 建议（可后续优化）
- Anti-Goals 覆盖评估
- 测试覆盖评估

---

## 五、测试阶段：/gen-test

### 什么时候用？
- 写完代码，需要补测试
- 想确保 Anti-Goals 都有测试覆盖

### 用法
```
/gen-test app/Services/InvoiceService.php
/gen-test app/Invoice.php
/gen-test InvoiceController
```

### 会生成什么？

**针对 Service 层**（单元测试，覆盖率目标 90%）
```php
/** @test */
public function it_calculates_discount_in_priority_order()     // 正常流程
/** @test */
public function it_requires_approval_when_discount_exceeds_500() // AG-001
/** @test */
public function it_rejects_refund_exceeding_paid_amount()       // AG-002
/** @test */
public function it_uses_bcmath_for_amount_calculation()         // AG-005
```

**针对 Model 层**（状态流转测试，覆盖率 100%）
```php
/** @test */
public function it_can_transit_from_waiting_to_called()          // 合法路径
/** @test */
public function it_throws_when_transit_from_waiting_to_completed() // 非法路径
```

**金额边界值自动覆盖**
```
折扣：499.99（不审批）、500.00（不审批）、500.01（审批）
退款：99.99（不审批）、100.00（不审批）、100.01（审批）
```

---

## 六、Bug 修复：/fix-bug（增强版）

### 与之前有什么不同？

新增了 **Step 7: Anti-Goals 回归检查**——修复 Bug 后自动验证没有引入新的规则违反。

```
/fix-bug 发票折扣计算结果多出小数位
```

输出报告新增两个部分：

```
**AI 推理链：**
1. 我读取了 InvoiceService.php:calculateDiscounts()
2. 我注意到第 87 行使用了 $subtotal * $rate（浮点乘法）
3. 我排除了"字段类型问题"，因为 decimal:2 cast 已正确设置
4. 确认根因是 PHP 浮点运算精度丢失

**Anti-Goals 回归检查：**
- AG-005 (bcmath): ✅ 修复后使用 bcmul()
- AG-001 (折扣审批): ✅ 审批阈值逻辑未受影响
- AG-006 (优先级): ✅ 计算顺序未改变
```

---

## 七、会话结束：/scratchpad

### 什么时候用？
- 复杂任务完成后，记录决策和遗留问题
- 下次继续工作时可以快速恢复上下文

### 用法
```
/scratchpad 收费模块开发
/scratchpad                    # 不指定类型也行，AI 会自动推断
```

### 会生成什么？

在 `ai-dev-template/SCRATCHPAD.md` 顶部插入一条记录：

```markdown
---
日期：2026-03-08  任务类型：收费模块开发  关联切片：切片 B

### 本次完成了什么
- InvoiceService 折扣计算逻辑（四级优先级）
- 折扣审批流程（BR-035）
- 对应 PHPUnit 测试 12 个方法

### 关键决策
| 决策内容     | 选择方案      | 原因摘要                  |
|-------------|--------------|--------------------------|
| 金额精度     | bcmul()      | 浮点运算折扣叠加后有误差   |

### AI 推理链
决策：金额精度方案
1. 我读取了 CLAUDE.md：「所有金额字段使用 decimal:2 cast」
2. 我注意到 Invoice 模型对金额字段使用了 decimal:2 cast
3. 我考虑了「PHP 原生浮点」和「bcmath」
4. 浮点的问题：0.1 + 0.2 !== 0.3，多级折扣叠加后误差累积
5. 选择 bcmath，因为医疗收费场景不允许金额误差

### 遗留问题
- [ ] 优惠券与会员折扣叠加规则待确认
---
```

下次开始新会话时，AI 会读取这条记录快速恢复上下文。

### 保存后下次怎么继续？

1. **新会话里先给上下文**  
   在第一条消息里说明「接着上次做」并引用 SCRATCHPAD，例如：
   ```
   接着上次的处方模块重构做，见 @ai-dev-template/SCRATCHPAD.md 最新一条。
   遗留问题里下一步是：……
   ```
   或直接：
   ```
   继续处方模块重构，按 SCRATCHPAD 最新条目的「遗留问题」接着做。
   ```

2. **AI 会做什么**  
   - 读取 `SCRATCHPAD.md` 顶部最新一条（本次完成了什么、关键决策、推理链、**遗留问题**）。
   - 结合 `intent.md`（及当前任务）恢复上下文。
   - 从「遗留问题（下次会话继续）」列表接着执行；若你要做的是架构/设计，会按 AGENT-PROTOCOL 的「阶段一」读 SCRATCHPAD + intent 做技术设计。

3. **建议**  
   - 遗留问题写具体一点（例如「步骤 3：患者详情页处方 Tab 联调」），下次一句就能接着做。
   - 若跨了很多天或任务很多，可先说「先看 SCRATCHPAD 最新一条，再决定从哪一步继续」。

---

## 八、典型开发全流程示例

以「添加技工单质量评分统计功能」为例：

```
步骤 1  /spec-discovery 添加技工单质量评分统计功能
        → AI 追问 → 生成 Anti-Goals → 审查安全
        → 输出 intent.md 更新建议 → 你审定

步骤 2  /arch-check 技工单质量评分统计
        → 6 项合规检查全通过
        → 输出文件清单和实现步骤

步骤 3  /implement 在 LabCaseService 中添加质量评分统计方法
        → 自动加载切片 D
        → 写代码 → Anti-Goals 检查 → @AiGenerated 标注
        → VIBE-CHECKLIST 六关自检

步骤 4  /gen-test app/Services/LabCaseService.php
        → 生成状态流转测试 + Anti-Goals 测试 + 边界值测试

步骤 5  /vibe-check
        → 快速过六关，确认无严重问题

步骤 6  /code-review app/Services/LabCaseService.php
        → 详细审查，输出三级报告

步骤 7  /scratchpad 技工单质量评分
        → 记录本次决策和遗留问题
```

---

## 九、命令关系图

```
需求阶段                实现阶段                质量阶段
─────────            ─────────            ─────────

/spec-discovery ──→ /arch-check ──→ /implement ──→ /vibe-check
     │                                  │              │
     │                                  ↓              ↓
     │                            /gen-test      /code-review
     │                                  │
     ↓                                  ↓
  intent.md                      TEST-SPEC.md
  state-machines.yaml            test-matrix.md

                    故障阶段                记录阶段
                    ─────────            ─────────

                    /fix-bug ──────────→ /scratchpad
                    （含推理链 +            （记录决策 +
                     Anti-Goals 回归）      遗留问题）
```

---

## 十、ai-dev-template 文件与命令的对应关系

| ai-dev-template 文件          | 被哪些命令使用                                   |
|------------------------------|------------------------------------------------|
| intent.md (Anti-Goals)       | 全部命令都会引用                                 |
| state-machines.yaml          | /implement, /gen-test, /vibe-check, /code-review, /arch-check |
| rule-engine.md               | /implement, /gen-test, /vibe-check, /code-review, /fix-bug |
| VIBE-CHECKLIST.md            | /vibe-check, /implement (Phase 4), /code-review |
| CONTEXT-SLICE.md             | /implement (Phase 1.5)                          |
| TEST-SPEC.md                 | /gen-test, /code-review                         |
| SCRATCHPAD.md                | /scratchpad, /spec-discovery                    |
| test-matrix.md + mock-data.md| /gen-test                                       |
| error-codes.md               | /implement (API 开发), /code-review              |
| PROMPT-VERSIONS/             | 所有 Prompt 版本记录（进阶使用）                  |
