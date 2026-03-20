---
description: 对最近改动或指定文件跑 VIBE-CHECKLIST 六关验收门禁
argument: 文件路径、git范围（如 HEAD~1）或功能描述（可选，默认检查未提交的改动）
---

## 执行流程

### Step 1: 确定检查范围

- 有参数且是文件路径 → 检查该文件
- 有参数且是 git 范围（如 `HEAD~3`）→ `git diff --name-only` 获取文件列表
- 无参数 → `git diff --name-only HEAD` 检查未提交的改动

### Step 2: 加载检查基准

读取：
- `ai-dev-template/VIBE-CHECKLIST.md` — 六关门禁
- `ai-dev-template/ai-spec/domain/intent.md` — Anti-Goals
- `ai-dev-template/ai-spec/domain/state-machines.yaml` — 状态机
- `ai-dev-template/ai-spec/engine/rule-engine.md` — 规则 Key

### Step 3: 逐关检查

对检查范围内的每个文件，执行六关门禁：

**【第一关】意图对齐**
- 检查代码是否引入了 intent.md 未定义的业务规则
- 检查涉及的 Anti-Goals 是否被违反

**【第二关】上下文遗漏**
- 检查状态值是否匹配 state-machines.yaml
- 检查字段名是否匹配 entities.md
- 检查枚举是否使用 DictItem 而非硬编码

**【第三关】AI 幻觉风险**
- 检查是否引用了不存在的 Model 字段/关系
- 检查是否有硬编码的魔法数字（应引用 rule-engine.md）

**【第四关】医疗场景专项**
- 金额计算：是否使用 `bc*()` 函数？
- 状态流转：是否在 Model 方法中？
- 敏感信息：NIN 是否出现在 `Log::`/`\Log::` 中？
- 审批流程：折扣/退款阈值是否正确处理？
- 软删除：查询是否包含 `whereNull('deleted_at')` 或使用 SoftDeletes？

**【第五关】代码质量底线**
- 搜索 `dd(` / `dump(` / `var_dump(` 残留
- 搜索裸 `catch (\Exception` 吞异常
- 检查 Blade 文件内联 `<style>` / `<script>` 大段代码
- 检查硬编码中文（应使用 `__()`）

**【第六关】重构信号检测**
- 计算方法行数（> 30 行警告）
- 计算 if 嵌套层级（> 3 层警告）
- 检查 `// @AiGenerated` 标注情况

### Step 4: 输出报告

```
## VIBE-CHECK 报告

**检查范围：** [文件列表]
**检查日期：** $currentDate

| 关卡 | 结果 | 发现问题数 |
|------|------|-----------|
| 第一关：意图对齐 | ✅/❌ | 0 |
| 第二关：上下文遗漏 | ✅/❌ | 0 |
| 第三关：AI 幻觉 | ✅/❌ | 0 |
| 第四关：医疗场景 | ✅/❌ | 0 |
| 第五关：代码质量 | ✅/❌ | 0 |
| 第六关：重构信号 | ✅/⚠️ | 0 |

### 发现的问题

#### 严重（必须修复）
[无 / 列表]

#### 一般（建议修复）
[无 / 列表]

#### 提示（可后续处理）
[无 / 列表]

### 总结
严重：__ / 一般：__ / 提示：__
```
