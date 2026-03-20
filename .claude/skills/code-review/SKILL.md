---
name: code-review
description: AI 代码审查，以 VIBE-CHECKLIST 六关门禁 + Anti-Goals 为基准，输出三级问题报告
---

## Usage

### How to Invoke This Skill

```
/code-review <文件路径或功能描述>
```

### What This Skill Does

以 `ai-dev-template/VIBE-CHECKLIST.md` 六关门禁和 `ai-dev-template/ai-spec/domain/intent.md` Anti-Goals 为基准，对代码进行结构化审查，输出严重/一般/建议三级问题报告。

### Common Use Cases

| 场景 | 用法 |
|------|------|
| 审查单个文件 | `/code-review app/Services/InvoiceService.php` |
| 审查最近改动 | `/code-review 最近修改的文件` |
| 审查特定功能 | `/code-review 收费模块折扣计算逻辑` |
| 审查 PR 差异 | `/code-review git diff 的内容` |

---

## 审查工作流

### Step 1: 加载审查基准

读取以下文件作为审查标准：
1. `ai-dev-template/ai-spec/domain/intent.md` — Anti-Goals 清单
2. `ai-dev-template/ai-spec/domain/state-machines.yaml` — 状态流转规则
3. `ai-dev-template/ai-spec/engine/rule-engine.md` — 业务规则 Key
4. `ai-dev-template/VIBE-CHECKLIST.md` — 六关验收门禁
5. `CLAUDE.md` — 项目技术规范

### Step 2: 确定审查范围

- 如果指定了文件路径 → 审查该文件
- 如果说"最近修改" → `git diff --name-only HEAD~1` 获取文件列表
- 如果说功能名 → 通过 grep 定位相关文件
- 对每个文件，同时定位其对应的测试文件

### Step 3: 逐关审查

#### 【第一关】意图对齐
- 代码是否解决了 intent.md 里的核心业务问题？
- Anti-Goals 有没有被违反？逐条核对涉及的条目
- 有没有发明 intent.md 未定义的业务规则？

#### 【第二关】上下文遗漏
- state-machines.yaml 中所有状态都被正确处理了？
- 命名与 CLAUDE.md / entities.md 一致？
- DictItem 枚举是否使用数据字典而非硬编码？

#### 【第三关】AI 幻觉风险
- 没有自行发明不存在的字段或关系？
- 没有硬编码魔法数字？应引用 rule-engine.md
- 没有引入项目不存在的 PHP 包或 JS 库？

#### 【第四关】医疗场景专项
- 金额计算使用 `decimal:2` cast + `bc*()` 函数？
- 状态流转在 Model 方法中封装？
- 患者敏感信息没有出现在日志中？
- 折扣 > 500 元触发审批？退款 > 100 元触发审批？
- 软删除查询正确处理？

#### 【第五关】代码质量底线
- 无 `dd()` / `dump()` / `var_dump()` 残留？
- 无裸 `catch (\Exception $e)` 吞异常？
- Blade 视图无内联大段 CSS/JS？
- 翻译使用 `__()` / `LanguageManager.trans()`？

#### 【第六关】重构信号
- 类职责 ≤ 1？
- Service 方法 ≤ 30 行？
- if 嵌套 ≤ 3 层？
- 复杂逻辑标注 `// @AiGenerated`？

### Step 4: 测试覆盖检查

- 是否存在对应的测试文件？
- Anti-Goals 中涉及的条目是否有测试方法覆盖？
- 状态流转是否有合法/非法路径的测试？

### Step 5: 输出报告

```
## Code Review 报告

**审查范围：** [文件列表]
**审查日期：** [日期]

---

### 严重问题（阻断合并）
1. **[文件:行号]** 问题描述
   违反规范：[AG-xxx / RULE-xxx / CLAUDE.md 条目]
   修复建议：[具体改法]

### 一般问题（需修复后合并）
1. **[文件:行号]** 问题描述
   违反规范：[引用来源]
   修复建议：[具体改法]

### 建议（可后续优化）
1. **[文件:行号]** 问题描述

---

### VIBE-CHECKLIST 结果
| 关卡 | 结果 | 说明 |
|------|------|------|
| 第一关：意图对齐 | ✅/❌ | |
| 第二关：上下文遗漏 | ✅/❌ | |
| 第三关：AI 幻觉 | ✅/❌ | |
| 第四关：医疗场景 | ✅/❌ | |
| 第五关：代码质量 | ✅/❌ | |
| 第六关：重构信号 | ✅/⚠️ | |

### 测试覆盖评估
- Anti-Goals 覆盖：__/__
- 状态流转覆盖：__/__
- 建议补充的测试：[列表]

### 总结
严重：__ / 一般：__ / 建议：__
结论：[通过 / 需修复后重新 Review / 阻断合并]
```

---

## 问题严重级别定义

| 级别 | 定义 | 处理方式 |
|------|------|---------|
| **严重** | 违反 Anti-Goals、金额精度错误、安全漏洞、状态非法跳跃 | 阻断合并，必须修复 |
| **一般** | 翻译硬编码、缺少权限中间件、Blade 内联脚本、缓存遗漏 | 需修复后合并 |
| **建议** | 方法过长、代码重复、缺少 @AiGenerated 标注 | 可后续优化 |
