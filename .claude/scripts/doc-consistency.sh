#!/bin/bash
# 检查 CLAUDE.md 与实际代码的一致性
# 用法: .claude/scripts/doc-consistency.sh

claude -p "审计 CLAUDE.md 与实际代码的一致性，检查以下内容：

1. **API 端点**：CLAUDE.md 中列出的所有 API v1 端点是否在 routes/api/v1.php 中实际存在？
2. **技术栈版本**：CLAUDE.md 中声明的包版本是否与 composer.json/composer.lock 一致？
3. **模块结构**：CLAUDE.md 中列出的模块是否都存在于 Modules/ 目录下？
4. **关键表**：CLAUDE.md 中列出的表是否有对应的 migration 文件？

对每项检查，输出：
- ✅ 一致
- ❌ 不一致（说明差异）

最后给出需要更新 CLAUDE.md 的具体建议。" \
  --allowedTools "Read,Glob,Grep,Bash"
