---
name: db-workflow
description: 数据库开发工作流指南，涵盖迁移和种子数据生成的项目规范，提供 /db-migration 和 /db-seeder 命令导航
---

## Usage

### How to Invoke This Skill

```
/db-workflow
```

### What This Skill Does

当调用此技能时，提供：
1. **项目数据库开发规范** — 迁移和种子文件的完整约定
2. **命令导航** — 快速访问 `/db-migration` 和 `/db-seeder`
3. **常见场景指引** — 新增模块、修改表结构、补充数据的操作流程

### Common Use Cases

| 场景 | 操作 |
|------|------|
| 新增功能模块 | 先 `/db-migration create {table}`，再 `/db-seeder {table} --register` |
| 给已有表加字段 | `/db-migration alter {table} add {field}:{type}` |
| 补充基础数据 | `/db-seeder {table} --register` |
| 查看数据库规范 | `/db-workflow` |

### Quick Start Examples

**Example 1: 新建表 + 种子数据**
```
/db-migration create treatment_categories
/db-seeder treatment_categories --register
```

**Example 2: 给已有表加字段**
```
/db-migration alter patients add referral_source:string:nullable
```

**Example 3: 只生成种子数据**
```
/db-seeder quick_phrase_categories --register
```

---

## 项目数据库规范

### 迁移规范

| 规则 | 说明 |
|------|------|
| 主键 | `$table->bigIncrements('id')` |
| 审计字段 | `$table->bigInteger('_who_added')->unsigned()->nullable()` |
| 时间戳 | `$table->timestamps()` + `$table->softDeletes()` |
| 外键约束 | **禁止使用** `$table->foreign()`，关联在应用层维护 |
| 类名 | 大驼峰：`CreateTreatmentCategoriesTable` |
| 文件名 | `YYYY_MM_DD_HHMMSS_descriptive_action.php` |
| 字段注释 | 中文行注释：`// 治疗分类名称` |
| 表名 | snake_case 复数形式 |
| `down()` | 必须正确回滚 |

### 种子数据规范

| 规则 | 说明 |
|------|------|
| 目录 | `database/seeds/`（无命名空间） |
| 重跑安全 | `DB::table('xxx')->truncate()` |
| 审计字段 | `$userId = DB::table('users')->first()->id ?? 1` |
| 进度提示 | `$this->command->info()` 开头和结尾 |
| 插入方式 | 数组 + `foreach` + `DB::table()->insert()` |
| 时间戳 | `'created_at' => now(), 'updated_at' => now()` |
| 数据访问 | 统一用 `DB::table()` facade，不用 Model |

### DatabaseSeeder 分组结构

```
[1/5] 基础配置    → 分支机构、角色、权限
[2/5] 用户数据    → 系统用户
[3/5] 角色权限    → 角色-权限关联
[4/5] 业务基础数据 → 服务项目、费用分类、库存分类、保险、会计科目
[5/5] 辅助数据    → 假期类型、节假日、患者标签、病历模板
```

新 seeder 根据数据性质插入对应分组。

---

## 反模式（不要做的事）

### 迁移反模式
- **不要添加外键约束**：禁止 `$table->foreign('xxx')->references('id')->on('table')`
- **不要省略 `down()` 方法**：即使觉得不会回滚，也必须实现
- **不要省略 `softDeletes()`**：除非表确实不需要软删除
- **不要省略 `_who_added`**：除非是纯配置表（如 settings）
- **不要使用自增 `increments()`**：统一使用 `bigIncrements()`

### 种子反模式
- **不要硬编码 user ID**：如 `'_who_added' => 1`，必须用 `$userId` 变量
- **不要批量插入**：如 `DB::table()->insert($allData)`，必须逐条插入
- **不要省略 `truncate()`**：除非明确是追加数据
- **不要使用 Model**：如 `Treatment::create()`，统一用 `DB::table()`
- **不要省略时间戳**：每条数据都必须有 `created_at` 和 `updated_at`

---

## 关联命令

| 命令 | 说明 |
|------|------|
| `/db-migration` | 生成迁移文件（建表、改表） |
| `/db-seeder` | 生成种子文件（含 DatabaseSeeder 注册） |

---

## 常见字段类型参考

| 业务场景 | 推荐字段定义 |
|---------|-------------|
| 名称 | `$table->string('name')` |
| 描述 | `$table->text('description')->nullable()` |
| 状态 | `$table->enum('status', ['Active', 'Inactive'])->default('Active')` |
| 排序 | `$table->integer('display_order')->default(0)` |
| 启用 | `$table->boolean('is_active')->default(true)` |
| 金额 | `$table->decimal('amount', 10, 2)->default(0)` |
| 关联 ID | `$table->bigInteger('xxx_id')->unsigned()->nullable()` |
| 日期 | `$table->date('start_date')->nullable()` |
| JSON | `$table->json('metadata')->nullable()` |
| 编码 | `$table->string('code', 50)->unique()` |
