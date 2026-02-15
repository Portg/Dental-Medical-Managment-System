#!/bin/bash
# 批量审计 Blade 文件中的硬编码中文文本
# 用法: .claude/scripts/i18n-audit.sh [目录路径]

DIR="${1:-resources/views}"

claude -p "扫描 $DIR 下所有 Blade 文件（.blade.php）。
找出所有硬编码的中文文本（即没有使用 __()、trans()、@lang() 包裹的中文）。
排除 HTML 注释和 PHP 注释中的中文。

输出格式为表格：
| 文件 | 行号 | 硬编码文本 | 建议的翻译 key |

只列出前 50 个问题。最后统计：共扫描多少文件，发现多少处硬编码。" \
  --allowedTools "Read,Glob,Grep"
