#!/bin/bash
# PostToolUse hook: PHP syntax check after Edit/Write
# Receives JSON on stdin with tool_input.file_path

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

# Only check PHP files
if [[ -n "$FILE_PATH" && "$FILE_PATH" == *.php ]]; then
    RESULT=$(php -l "$FILE_PATH" 2>&1)
    if [[ $? -ne 0 ]]; then
        echo "PHP syntax error detected:"
        echo "$RESULT"
        exit 1
    fi
fi
