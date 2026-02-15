---
name: arch-check
description: 架构自检式规划，提出方案前先验证模块依赖合规性，不合规的自己换方案，减少用户纠正次数
---

## Usage

### How to Invoke This Skill

```
/arch-check <功能描述>
```

### What This Skill Does

当调用此技能时，在提出任何方案前先做架构合规自检：
1. **读取约束** — 加载 CLAUDE.md 中的架构原则
2. **设计方案** — 确定新类/改动的位置
3. **自检合规** — 逐条验证模块依赖、SRP、技术选型
4. **自动纠正** — 不合规的自己换方案，不给用户看违规方案

### Common Use Cases

| 场景 | 用法 |
|------|------|
| 新增功能 | `/arch-check 添加患者导出 PDF 功能` |
| 跨模块需求 | `/arch-check Doctor 模块需要读取 Receptionist 的预约数据` |
| 新增类 | `/arch-check 创建一个通知服务处理短信和邮件发送` |
| 重构设计 | `/arch-check 将发票计算逻辑从 Controller 移到独立模块` |

---

## 自检工作流

### Step 1: 加载架构约束

读取以下文件，提取所有架构规则：
- `CLAUDE.md` — 项目架构原则、模块结构、技术栈
- 相关模块的现有代码结构

### Step 2: 设计初始方案

1. 列出需要新建或修改的类/文件
2. 标注每个类/文件所在的模块
3. 标注每个类的依赖关系（import 了什么、被谁使用）

### Step 3: 逐条合规检查

对每个新建的类或改动，执行以下检查：

#### 检查 1: 模块依赖方向
```
类 [X] 在 [模块A]，依赖 [模块B] 的 [Y]
→ 依赖方向：[模块A] → [模块B]
→ 是否允许：[是/否]
→ 依据：app/ → Modules/* 允许，反向不允许，Modules 间不允许
```

#### 检查 2: 单一职责
```
类 [X] 的职责：
1. [职责1]
2. [职责2]
→ 是否符合 SRP：[是/否]
→ 如果否，拆分建议：[...]
```

#### 检查 3: 技术选型
```
使用了 [技术/包]
→ 是否在项目技术栈内：[是/否]
→ 版本是否兼容：[是/否]
```

#### 检查 4: 跨模块通信
```
[模块A] 需要访问 [模块B] 的数据
→ 通信方式：[直接引用 / Event / 共享 Service]
→ 是否合规：[是/否]
→ 如果否，替代方案：[...]
```

### Step 4: 自动纠正

- 如果任何检查**不通过**：
  1. 不要把违规方案展示给用户
  2. 设计一个合规的替代方案
  3. 对替代方案重新执行 Step 3
  4. 重复直到所有检查通过

### Step 5: 输出合规方案

```
## 架构方案

**功能：** [一句话描述]

**方案概要：**
[整体设计思路，2-3 句话]

**文件清单：**
| 操作 | 文件 | 模块 | 说明 |
|------|------|------|------|
| 新建 | app/Services/XxxService.php | app | [职责] |
| 修改 | Modules/Doctor/Http/Controllers/XxxController.php | Doctor | [改动] |
| 新建 | resources/lang/zh-CN/xxx.php | - | [翻译] |

**约束合规检查：**
| # | 规则 | 结果 | 说明 |
|---|------|------|------|
| 1 | 模块依赖方向 | ✅ | app/ → Modules/Doctor，方向正确 |
| 2 | 单一职责 | ✅ | Service 只负责业务逻辑 |
| 3 | 技术选型 | ✅ | 使用项目现有的 barryvdh/laravel-dompdf v2 |
| 4 | 跨模块通信 | ✅ | 通过 app/Services 共享，无 Modules 间直接依赖 |

**实现步骤：**
1. [检查点1]
2. [检查点2]
3. [检查点3]
```

用户确认后，按检查点顺序逐步实现。

---

## 本项目架构规则速查

### 模块依赖图

```
app/（核心层）
├── Http/Controllers/      ← 核心控制器
├── Models/                ← 所有 Eloquent 模型
├── Services/              ← 共享业务逻辑（跨模块通信的桥梁）
├── Events/ + Listeners/   ← 事件驱动解耦
└── Providers/             ← 服务注册

Modules/*（业务模块层）— 可以依赖 app/，不能互相依赖
├── Doctor/
├── Nurse/
├── Receptionist/
├── SuperAdmin/
└── Pharmacy/
```

### 允许的依赖

| 从 | 到 | 允许？ |
|----|-----|--------|
| Modules/* | app/ | ✅ |
| app/ | Modules/* | ❌ |
| Modules/A | Modules/B | ❌ |
| Modules/* | Laravel/vendor | ✅ |

### 跨模块通信方式

| 方式 | 适用场景 | 示例 |
|------|---------|------|
| 共享 Service（app/Services/） | 多模块需要同一业务逻辑 | PatientService |
| Event + Listener | 模块 A 的操作需触发模块 B 的动作 | AppointmentCreated → 发送通知 |
| 共享 Model（app/Models/） | 多模块访问同一数据表 | Patient, Appointment |
