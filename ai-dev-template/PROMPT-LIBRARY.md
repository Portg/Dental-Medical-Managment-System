# PROMPT-LIBRARY.md — Prompt 索引（口腔医疗管理系统）
# 本文件为索引，具体 Prompt 内容见 PROMPT-VERSIONS/ 对应目录

## 使用规则
1. 每次使用前，先确认使用的是 PROMPT-VERSIONS/ 中的最新推荐版本
2. 使用后在 SCRATCHPAD 记录版本号和效果反馈
3. 发现 Prompt 问题时，在 CHANGELOG.md 中记录，不要直接修改 Library

---

## Prompt 索引

### P-01  生成 API 接口层（Controller + Route）
当前推荐版本：PROMPT-VERSIONS/generate-api/v1.0.md
Golden Test 最新通过率：—/—（初始版本，待首次测试）
上次更新：2026-03-07  |  初始版本

### P-02  生成 Controller 层（Web）
当前推荐版本：PROMPT-VERSIONS/generate-controller/v1.0.md
Golden Test 最新通过率：—/—（初始版本，待首次测试）
上次更新：2026-03-07  |  初始版本

### P-03  生成 Service 层
当前推荐版本：PROMPT-VERSIONS/generate-service/v1.0.md
Golden Test 最新通过率：—/—（初始版本，待首次测试）
上次更新：2026-03-07  |  初始版本

### P-04  生成测试套件
当前推荐版本：PROMPT-VERSIONS/generate-test/v1.0.md
Golden Test 最新通过率：—/—（初始版本，待首次测试）
上次更新：2026-03-07  |  初始版本

### P-05  AI Code Review
当前推荐版本：PROMPT-VERSIONS/code-review/v1.0.md
Golden Test 最新通过率：—/—（初始版本，待首次测试）
上次更新：2026-03-07  |  初始版本

### P-06  SPEC 生成（Phase 1~3）
当前推荐版本：见 SPEC-DISCOVERY.md（不做版本化，每次对话定制）

---

## 版本降级程序

如果最新版本通过率低于上一版本：
1. 在 CHANGELOG.md 中标注「回退」
2. 将上一版本设为当前推荐版本
3. 分析原因后再尝试修复
