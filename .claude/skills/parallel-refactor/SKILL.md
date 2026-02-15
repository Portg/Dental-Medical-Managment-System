---
name: parallel-refactor
description: 并行多模块重构，用 Task 工具为每个模块派子 agent 并行执行，最后统一校验跨模块一致性
---

## Usage

### How to Invoke This Skill

```
/parallel-refactor <重构描述>
```

### What This Skill Does

当调用此技能时，作为协调者：
1. **分析重构范围** — 确定涉及哪些模块和文件
2. **派发子 agent** — 用 Task 工具为每个模块创建并行任务
3. **独立执行** — 每个子 agent 在自己的模块内完成重构 + 验证
4. **统一校验** — 所有子 agent 完成后检查跨模块一致性

### Common Use Cases

| 场景 | 用法 |
|------|------|
| Service 层抽取 | `/parallel-refactor 将所有 Controller 中超过 20 行的业务逻辑抽取到 Service` |
| i18n 批量修复 | `/parallel-refactor 将所有 Blade 中的硬编码中文替换为 trans()` |
| 权限审计 | `/parallel-refactor 检查所有路由是否配置了 can: 中间件` |
| 代码规范统一 | `/parallel-refactor 将所有 Controller 的 JSON 响应统一为标准格式` |

---

## 协调工作流

### Phase 1: 分析范围

1. 读取用户的重构描述
2. 确定涉及的模块列表：

```
目标模块：
├── app/Http/Controllers/     （核心控制器）
├── Modules/Doctor/           （医生模块）
├── Modules/Nurse/            （护士模块）
├── Modules/Receptionist/     （前台模块）
├── Modules/SuperAdmin/       （超管模块）
└── Modules/Pharmacy/         （药房模块）
```

3. 定义每个子 agent 的具体任务和验收标准

### Phase 2: 派发并行子 agent

使用 Task 工具为每个模块创建子 agent，prompt 模板：

```
你负责 [模块名] 模块的重构任务。

重构模式：[具体描述]

执行步骤：
1. 读取模块内所有相关文件
2. 按重构模式执行修改
3. 对每个修改的 PHP 文件运行 php -l 验证语法
4. 检查修改是否破坏了模块内的引用关系

模块路径：[模块路径]

完成后报告：
- 修改了哪些文件
- 每个文件改了什么
- 语法检查结果
- 发现的问题或风险
```

**关键：所有子 agent 必须并行启动（在同一条消息中发出多个 Task 调用）**

### Phase 3: 统一校验

所有子 agent 完成后：

1. **跨模块一致性检查**：
   - 共享接口/基类的签名是否一致？
   - Event/Listener 的契约是否匹配？
   - import 路径是否正确？

2. **全局验证**：
   ```bash
   # 所有 PHP 文件语法检查
   find app/ Modules/ -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"

   # 路由加载
   php artisan route:list
   ```

3. **修复整合问题**：如果子 agent 之间有冲突或遗漏，在此阶段修复

### Phase 4: 汇报

```
## 并行重构报告

**重构模式：** [一句话描述]

**模块执行结果：**
| 模块 | 修改文件数 | 状态 | 备注 |
|------|-----------|------|------|
| app/ | X | ✅ | - |
| Doctor | X | ✅ | - |
| Nurse | X | ✅ | - |
| Receptionist | X | ✅ | - |
| SuperAdmin | X | ✅ | - |
| Pharmacy | X | ✅ | - |

**跨模块校验：** ✅ 通过 / ❌ [问题说明]

**全局验证：**
- php -l: ✅ 全部通过
- route:list: ✅ 路由正常

**改动清单：**
[按模块分组列出所有改动文件]
```

---

## 架构约束（子 agent 必须遵守）

- 模块依赖方向：`app/` → `Modules/*`，不允许反向
- `Modules/*` 之间不允许直接互相依赖
- 跨模块通信只能通过 `app/` 层的共享 Service 或 Event
- 不要删除现有的 import/注解/provider，除非确认未使用
- UI 标签用中文（zh-CN）
