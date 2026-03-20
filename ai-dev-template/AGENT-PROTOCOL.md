# AGENT-PROTOCOL.md — Agent 交接契约（口腔医疗管理系统）

## 交接要素总览

| 交接要素   | 要求                                                              |
|------------|-------------------------------------------------------------------|
| 输出物     | spec + 代码 + Scratchpad 条目 + 测试文件 + Prompt 版本号          |
| 自检报告   | VIBE-CHECKLIST 逐项 + 放弃方案记录 + `@AiGenerated` 标注确认     |
| 下游验收   | 读 spec 后开工 + 上下文恢复确认                                    |
| 阻断条件   | spec 质量不足 / Scratchpad 缺失 / 测试缺失 / 推理链缺失           |
| Prompt 溯源 | 必须记录使用的 Prompt 版本                                        |

---

## 阶段一：需求 → Architect Agent

### 输入
- 用户需求描述
- intent.md（如已存在）
- SCRATCHPAD.md 最新条目

### 输出
- 更新 intent.md（含 Anti-Goals）
- 更新 entities.md（涉及的实体及字段）
- 更新 state-machines.yaml（涉及的状态机）
- 迁移文件设计（表结构变更）
- Scratchpad 条目（含推理链）

### 自检
- [ ] Anti-Goals 是否覆盖了所有边界场景？
- [ ] 实体设计是否与现有 92+ Model 兼容？
- [ ] 状态机是否完整（包含所有合法与非法路径）？

---

## 阶段二：Architect Agent → Coding Agent

### 交接包内容
- intent.md（已审定）
- entities.md + state-machines.yaml
- CONTEXT-SLICE 推荐的最小上下文切片
- 使用的 Prompt 版本号

### Coding Agent 输出
- 代码文件（Model / Controller / Service / Blade / JS / CSS）
- 迁移文件（数据库变更）
- 翻译文件（zh-CN + en）
- 测试文件（PHPUnit，遵循 TEST-SPEC.md）
- VIBE-CHECKLIST 自检记录
- Scratchpad 条目（含推理链 + Prompt 效果反馈）

### 强制要求
1. 代码必须遵循 CLAUDE.md 中的所有约束
2. 测试是任务的组成部分，不是事后补充
3. 复杂逻辑必须标注 `// @AiGenerated`
4. Blade 视图必须拆分 CSS/JS 为独立文件

---

## 阶段三：Coding Agent → Review Agent（最关键）

### 交接包内容
- 代码文件
- 测试文件（TEST-SPEC.md 规定的测试）
- 《生成决策说明》含推理链
- VIBE-CHECKLIST 检查记录（含第六关）
- Scratchpad 条目（含推理链）
- 使用的 Prompt 版本号

### Review Agent 检查项

#### 业务合规性检查
- [ ] 金额计算是否使用 `decimal:2` + `bc*()` 函数？
- [ ] 折扣 > 500 元是否触发审批（BR-035）？
- [ ] 病历修改是否走 Amendment 流程？
- [ ] 患者敏感信息（NIN）是否加密存储？
- [ ] 状态流转是否符合 state-machines.yaml？

#### 技术规范检查
- [ ] Controller 中是否只做验证和调用 Service？
- [ ] Service 中是否包含事务处理？
- [ ] Model 中状态常量是否与 DictItem 一致？
- [ ] 翻译 key 是否同步添加到 zh-CN 和 en？
- [ ] 软删除查询是否正确？

#### 测试质量检查
- [ ] Anti-Goals 中每条规则是否有对应测试方法？
- [ ] 测试方法名是否包含业务语义？
- [ ] 没有空测试或被跳过的测试？

#### `@AiGenerated` 标注检查
- [ ] 复杂逻辑是否已标注 `// @AiGenerated`？
- [ ] 标注中是否包含 `reviewBy` 日期？
- [ ] 已到期的标注是否已人工复核并移除？

#### Prompt 可追溯性检查
- [ ] 交接包中是否包含 Prompt 版本号？
- [ ] 该 Prompt 版本是否为 PROMPT-VERSIONS/ 中当前推荐版本？

### 阻断条件
- 测试文件缺失 → **阻断合并**
- Anti-Goals 对应测试用例不完整 → **阻断合并**
- 金额计算使用浮点运算 → **阻断合并**
- 状态流转不符合 state-machines.yaml → **阻断合并**
- Prompt 版本号缺失 → 要求补充（不阻断，但需记录）
- 存在过期 `@AiGenerated` → 标记为「一般」级问题

---

## 阶段四：Review Agent → 人工 Review + 部署

### 交接包内容
- Review 报告（三级问题分类：严重 / 一般 / 建议）
- 代码文件（已通过 Review）
- 测试执行结果

### 人工检查重点
- 检查 `@AiGenerated` 标注，决定是否人工重构
- 运行 `php artisan test` 验证全部测试通过
- 运行 PROMPT-GOLDEN-TESTS，更新 CHANGELOG
- Scratchpad 更新（记录上线决策）
