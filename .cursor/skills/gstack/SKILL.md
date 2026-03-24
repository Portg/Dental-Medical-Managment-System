---
name: gstack
description: >-
  Applies Garry Tan's gstack agent workflow (Think→Plan→Build→Review→Test→Ship→Reflect)
  and maps gstack slash-command roles to Cursor behavior. Use when the user mentions
  gstack, Garry Tan gstack, /office-hours, /plan-ceo-review, /review, /qa, /ship,
  or wants a structured AI "virtual team" process before coding.
---

# gstack（Cursor）

[gstack](https://github.com/garrytan/gstack) 是开源（MIT）的 Claude Code 技能集合，用 Markdown 技能模拟 CEO / 设计 / 工程 / QA / 发布等角色。Cursor 无内置 `/slash` 命令时，由本技能约束代理**按同一套流程与职责**工作。

## 何时启用

- 用户提到：gstack、Garry Tan、具体 slash（如 `/office-hours`、`/review`、`/qa`）
- 用户要求：先想清楚再写代码、多角色评审、上线前检查清单
- 用户已在本机或仓库安装 gstack 源码，需要代理配合其产物（设计文档、测试计划等）

## 核心流程（必须保持顺序意识）

```
Think → Plan → Build → Review → Test → Ship → Reflect
```

下游步骤应能消费上游产出（设计说明 → 评审清单 → 实现 → 代码审 → 验证 → 发布说明 → 回顾）。

## Slash 命令 → Cursor 行为映射

代理不假设终端里存在真实 `/foo` 命令；应**用自然语言与任务拆解**复现该角色的产出。

| gstack 命令 | 角色侧重 | 代理应做的事（摘要） |
|-------------|----------|------------------------|
| `/office-hours` | 产品澄清 | 用具体场景追问，挑战表述与前提，收敛最小可行切片，可输出简短设计要点 |
| `/plan-ceo-review` | CEO/范围 | 10 段式或等价的范围/价值审视，给出扩缩范围建议 |
| `/plan-eng-review` | 工程/架构 | 数据流、边界情况、失败路径、测试矩阵、安全假设 |
| `/plan-design-review` | 设计 | 多维度评分与「满分长什么样」，指出 AI 味/模板化 UI 风险 |
| `/design-consultation` | 设计系统 | 竞品/参考、设计系统建议、高风险创意点 |
| `/review` | Staff 审阅 | 找「过 CI 但线上会炸」类问题；能改则改并说明 |
| `/investigate` | 调试 | 无调查不修；追踪数据流；连续失败则停手汇报 |
| `/design-review` | 设计落地 | 与 plan-design-review 同级审计 + 可落地的界面修改 |
| `/qa` / `/qa-only` | QA | 真机/浏览器级验证（若环境允许）；仅报告时用 qa-only |
| `/cso` | 安全 | OWASP + STRIDE，低误报门槛，每条含利用思路 |
| `/ship` | 发布 | 测完、分支卫生、PR 描述完整 |
| `/land-and-deploy` | 上线 | 合并、等 CI/CD、验证生产健康 |
| `/canary` | SRE | 发布后监控与回归信号 |
| `/benchmark` | 性能 | 基线与 PR 前后对比 |
| `/document-release` | 文档 | README/变更说明与实现一致 |
| `/retro` | 回顾 | 周回顾：交付、测试健康、改进点 |
| `/browse` | 浏览器 | 真实页面操作与截图；**勿**与项目里禁用的浏览器 MCP 冲突时，遵守仓库 `CLAUDE.md` / `AGENTS.md` |
| `/setup-browser-cookies` | 会话 | 需要登录态时引导用户按 gstack 文档导入 cookie |
| `/codex` | 第二意见 | 跨模型/第二视角审查时可建议用户在另一 CLI 跑，代理汇总差异 |
| `/autoplan` | 流水线 | CEO→设计→工程评审串联，只把「品味/决策」留给用户 |
| `/careful` / `/freeze` / `/guard` / `/unfreeze` | 安全网 | 破坏性命令前警告；`/freeze` 限制编辑目录 |
| `/gstack-upgrade` | 升级 | 指向上游 `gstack-upgrade` 与 `./setup` |

## 安装 gstack 源码（可选，与上游一致）

若要在本仓库获得**与上游相同的 SKILL 生成物**（含 Codex/Cursor 类宿主），在仓库根执行：

```bash
git clone https://github.com/garrytan/gstack.git .agents/skills/gstack
cd .agents/skills/gstack && ./setup --host codex
```

或用户级一次安装：

```bash
git clone https://github.com/garrytan/gstack.git ~/gstack
cd ~/gstack && ./setup --host codex
# 或 ./setup --host auto
```

故障排查：`cd .agents/skills/gstack && ./setup`（或上游文档中的路径）。细节与隐私说明见 [reference.md](reference.md)。

## 与项目规则的关系

- 若 `CLAUDE.md` / `AGENTS.md` 要求使用 gstack 的 `/browse` 或禁止某些 MCP，**以项目文件为准**。
- 本技能不替代项目架构、i18n、Blade/JS 分离等既有规范。

## 额外资料

- 完整命令表、链接与安装细节：[reference.md](reference.md)
