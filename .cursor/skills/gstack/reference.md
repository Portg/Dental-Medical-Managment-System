# gstack 参考（Cursor / 本仓库技能）

## 上游仓库

- 源码：<https://github.com/garrytan/gstack>
- 技能深潜文档：`docs/skills.md`（仓库内）
- 理念：`ETHOS.md`；架构：`ARCHITECTURE.md`；浏览器：`BROWSER.md`

## 在 Cursor 中的典型安装方式

**仅本仓库（推荐与团队共享）：**

```bash
git clone https://github.com/garrytan/gstack.git .agents/skills/gstack
cd .agents/skills/gstack && ./setup --host codex
```

`setup` 在 `.agents/skills/gstack` 下运行时，会在同仓库生成/安装 Codex 兼容技能，**不会**写入 `~/.codex/skills`（与上游 README 一致）。

**用户全局：**

```bash
git clone https://github.com/garrytan/gstack.git ~/gstack
cd ~/gstack && ./setup --host codex
# 或 ./setup --host auto
```

## Claude Code 侧（若同时使用）

全局：

```bash
git clone https://github.com/garrytan/gstack.git ~/.claude/skills/gstack
cd ~/.claude/skills/gstack && ./setup
```

拷贝进项目：

```bash
cp -Rf ~/.claude/skills/gstack .claude/skills/gstack && rm -rf .claude/skills/gstack/.git
cd .claude/skills/gstack && ./setup
```

并在 `CLAUDE.md` 增加 gstack 小节（上游 README 提供模板）：强调用 gstack 的 `/browse` 做网页浏览、列出可用 slash、异常时 `./setup`。

## 上游列出的技能名（便于检索）

`/office-hours`, `/plan-ceo-review`, `/plan-eng-review`, `/plan-design-review`, `/design-consultation`, `/review`, `/ship`, `/land-and-deploy`, `/canary`, `/benchmark`, `/browse`, `/qa`, `/qa-only`, `/design-review`, `/setup-browser-cookies`, `/setup-deploy`, `/retro`, `/investigate`, `/document-release`, `/codex`, `/cso`, `/autoplan`, `/careful`, `/freeze`, `/guard`, `/unfreeze`, `/gstack-upgrade`

## 隐私与遥测（上游行为摘要）

- 默认关闭；若开启仅上报匿名元数据（技能名、耗时、成败、版本、OS等），不含代码与路径。
- 本地 `gstack-analytics` 可查看本地 JSONL 仪表板。

## `/browse` 失败时

上游建议：`cd ~/.claude/skills/gstack && bun install && bun run build`（路径随安装位置而变）。
