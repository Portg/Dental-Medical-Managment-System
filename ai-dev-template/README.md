# AI 开发规范模板 — 口腔医疗管理系统

## 四层架构全景

| 层级   | 解决的核心问题                         | 核心文件                                          |
|--------|----------------------------------------|---------------------------------------------------|
| 工程层 | AI 生成代码有结构可循                  | CLAUDE.md / entities.md / error-codes.md          |
| 意图层 | AI 理解「为什么」后再生成代码          | intent.md / VIBE-CHECKLIST / rule-engine.md       |
| 对话层 | SPEC 由对话生成，上下文最小化          | SPEC-DISCOVERY / CONTEXT-SLICE / SCRATCHPAD       |
| 质量层 | 代码可测试可重构，Prompt 可版本化可审计 | TEST-SPEC / PROMPT-VERSIONS / PROMPT-GOLDEN-TESTS |

## 文件速查表

| 文件                     | 核心作用                              | 使用时机                   |
|--------------------------|---------------------------------------|----------------------------|
| intent.md                | 业务意图 + Anti-Goals（最高优先级）    | 每次任务第一优先读取        |
| rule-engine.md           | 规则 Key 定义，禁止 AI 硬编码         | 涉及规则判断时              |
| VIBE-CHECKLIST.md        | AI 输出六关验收门禁                    | 每次 AI 生成代码后          |
| AGENT-PROTOCOL.md        | Agent 交接契约                        | 每次阶段交接                |
| SPEC-DISCOVERY.md        | SPEC 四阶段对话生成流程               | 创建/修改 intent.md 前      |
| CONTEXT-SLICE.md         | 任务上下文切片规则                    | 每次任务启动时查表          |
| SCRATCHPAD.md            | 跨会话记录（含推理链）                | 每次会话结束时填写          |
| TEST-SPEC.md             | 测试规范 + @AiGenerated 注解规范      | Coding Agent 生成任务时     |
| PROMPT-VERSIONS/         | Prompt 版本历史 + CHANGELOG           | 修改/使用 Prompt 时         |
| PROMPT-GOLDEN-TESTS.md   | Prompt 效果基准测试集                 | 每次修改 Prompt 后          |
| PROMPT-LIBRARY.md        | Prompt 索引（含版本号 + 通过率）      | 按任务类型选用              |
| CLAUDE.md                | 技术规范 + AI 行为约束                | 被切片引用（项目根目录）     |

## 完整开发流程

```
【阶段 0】需求触发
  ↓
【阶段 1】SPEC 对话生成
  SPEC-DISCOVERY Phase 1~3 → 生成 intent.md
  ↓
【阶段 2】Architect Agent
  读 SCRATCHPAD + intent.md → 生成技术设计
  ↓
【阶段 3】Coding Agent
  查 CONTEXT-SLICE → 生成代码 + 测试
  过 VIBE-CHECKLIST 六关自检
  ↓
【阶段 4】Review Agent
  Anti-Goals 基准 Review + 测试覆盖检查
  ↓
【阶段 5】人工 Review + 部署
```

## 技术栈

- **后端**: PHP 8.2+, Laravel 11.x, MySQL 5.7+
- **前端**: Bootstrap, jQuery, Yajra DataTables, FullCalendar, Select2
- **测试**: PHPUnit 11.x, Mockery
- **国际化**: zh-CN (主), en
