---
name: implement
description: 实现任务时注入架构约束和工作流程，确保模块依赖正确、代码质量达标、编译验证通过
---

## Usage

### How to Invoke This Skill

```
/implement <任务描述>
```

### What This Skill Does

当调用此技能时，强制执行以下工作流：
1. **架构合规检查** — 验证模块依赖方向、类放置位置
2. **实现优先** — 直接写代码，不要停在计划阶段
3. **编译验证** — 每次修改后验证语法/编译
4. **变更审计** — 确保没有误删 import/注解/provider

### Common Use Cases

| 场景 | 用法 |
|------|------|
| 新增功能 | `/implement 在患者列表页添加导出功能` |
| 重构代码 | `/implement 将 PatientController 中的业务逻辑抽取到 Service 层` |
| 跨模块功能 | `/implement 在 Doctor 模块中调用 Receptionist 的预约数据` |
| 批量修改 | `/implement 为所有 Controller 添加 FormRequest 验证` |

---

## 架构原则（实现前必读）

### 模块依赖规则

```
app/（核心）← Modules/*（业务模块）
```

- `app/` 中的代码 **不得** 依赖 `Modules/*` 中的类
- `Modules/*` 之间 **不得** 直接互相依赖
- 跨模块通信使用 **事件（Event）** 或 **共享 Service（app/ 层）**

### 类放置检查清单

在创建新类之前，确认：
1. 这个类属于哪个模块？依据是什么？
2. 它的依赖关系是否符合上述方向？
3. 是否需要在 `app/` 层创建共享接口？

### 禁止事项

- **不要** 把多个不相关职责合并到一个类中（违反 SRP）
- **不要** 在不确定的情况下猜测类应该放在哪里 — 先分析依赖图
- **不要** 引入新的包依赖而不验证版本兼容性

---

## 实现工作流

### Phase 1: 理解（≤ 2 分钟）

1. 读取相关文件，理解现有代码结构
2. 确认修改涉及哪些文件
3. 检查是否有现有的 pattern 可以复用

### Phase 2: 实现（主要时间花在这里）

1. **逐文件修改**，每个文件改完后：
   - 运行 `php -l <file>` 验证语法
   - 检查 import/use 语句完整性
2. **不要删除** 现有的：
   - `use` import 语句（除非确认未使用）
   - Service Provider 注册
   - Middleware 注册
   - `config/app.php` 中的 providers/aliases
3. 遇到跨模块需求时，优先考虑：
   - 通过 `app/Services/` 共享
   - 通过 Laravel Event/Listener 解耦

### Phase 3: 验证

1. 运行 `php artisan route:list` 检查路由完整性
2. 运行 `php -l` 对所有修改的 PHP 文件做语法检查
3. 检查修改是否影响了其他文件（grep 引用）
4. 确认 i18n：新增的用户可见文本是否使用了 `__()` 或 `trans()`

---

## 语言与文档规范

- UI 标签和提示信息：**中文**（zh-CN 为主语言）
- 代码注释：中文或英文均可，保持与周围代码一致
- 新增翻译 key 时，同时更新 `en/` 和 `zh-CN/` 语言文件
- 设计文档：**中文**

---

## 技术栈参考

| 层 | 技术 | 版本 |
|----|------|------|
| 后端框架 | Laravel | 11.x |
| PHP | - | 8.2+ |
| 数据库 | MySQL | 5.7+ |
| 模块化 | nwidart/laravel-modules | v11 |
| 认证 | laravel/sanctum | v4 |
| PDF | barryvdh/laravel-dompdf | v2 |
| Excel | maatwebsite/excel | v3.1 |
| DataTables | yajra/datatables | v10 |
| 前端 | Bootstrap + jQuery + Select2 | - |

引入新依赖前，必须验证与上述版本的兼容性。

---

## 反模式（不要做的事）

- **不要只做计划不写代码** — 除非用户明确要求"只做分析"
- **不要一次性改完所有文件再验证** — 逐文件验证
- **不要猜测模块归属** — 先分析依赖方向
- **不要引入不必要的抽象** — 三行重复代码优于过早抽象
- **不要添加用户没要求的功能** — 不要过度工程化
