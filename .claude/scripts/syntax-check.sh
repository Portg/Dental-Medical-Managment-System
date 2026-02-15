#!/bin/bash
# 对所有已修改的 PHP 文件做语法检查，发现错误自动修复
# 用法: .claude/scripts/syntax-check.sh

claude -p "执行以下步骤：
1. 运行 git diff --name-only HEAD 找出所有已修改的 PHP 文件
2. 对每个 PHP 文件运行 php -l 做语法检查
3. 如果发现语法错误，读取该文件，分析错误原因并修复
4. 修复后重新运行 php -l 确认通过
5. 最后运行 php artisan route:list --json 确认路由加载正常

输出：修复了哪些文件的什么问题，最终结果是否全部通过。" \
  --allowedTools "Bash,Read,Edit"
