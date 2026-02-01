---
description: 生成符合项目规范的数据库迁移文件
---

# Database Migration Generator

根据用户描述，生成符合本项目规范的 Laravel 数据库迁移文件。

## 输入格式

用户可通过 `$ARGUMENTS` 传入参数，支持以下格式：

```
/db-migration                                                → 交互式：询问表名、动作、字段
/db-migration create treatment_categories                    → 建表（交互补充字段）
/db-migration alter patients add phone_backup:string:nullable → 给已有表加字段
```

若 `$ARGUMENTS` 为空，进入交互式引导，依次询问：
1. 动作类型（create 建表 / alter 改表）
2. 表名
3. 字段列表（名称:类型:修饰符，逗号分隔）
4. 中文用途说明（用于注释）

## 项目迁移规范（必须遵循）

### 通用约定
- **主键**：`$table->bigIncrements('id')`
- **审计字段**：`$table->bigInteger('_who_added')->unsigned()->nullable()`
- **时间戳**：`$table->timestamps()` + `$table->softDeletes()`
- **不加外键约束**：字段只定义列类型（如 `bigInteger()->unsigned()`），**禁止**使用 `$table->foreign()`。关联关系在应用层维护，不通过数据库外键约束
- **类名**：大驼峰，如 `CreateTreatmentCategoriesTable`
- **文件名**：`YYYY_MM_DD_HHMMSS_descriptive_action.php`，时间戳用当前时间
- **字段注释**：每个业务字段加中文行注释 `// 治疗分类名称`
- **`down()` 方法**：必须正确回滚（建表用 `dropIfExists`，加字段用 `dropColumn`）
- **表名**：蛇形命名（snake_case）、复数形式

### 字段类型速查
| 简写 | Blueprint 方法 | 说明 |
|------|---------------|------|
| `string` | `$table->string('name')` | VARCHAR 255 |
| `string:100` | `$table->string('name', 100)` | VARCHAR 指定长度 |
| `text` | `$table->text('name')` | TEXT |
| `integer` | `$table->integer('name')` | INT |
| `bigint` | `$table->bigInteger('name')` | BIGINT |
| `boolean` | `$table->boolean('name')` | TINYINT(1) |
| `decimal` | `$table->decimal('name', 10, 2)` | DECIMAL |
| `date` | `$table->date('name')` | DATE |
| `datetime` | `$table->dateTime('name')` | DATETIME |
| `enum` | `$table->enum('name', ['A','B'])` | ENUM |
| `json` | `$table->json('name')` | JSON |

### 修饰符
| 修饰符 | 说明 |
|--------|------|
| `nullable` | `->nullable()` |
| `default:value` | `->default('value')` |
| `after:column` | `->after('column')` |
| `unique` | `->unique()` |
| `index` | `->index()` |
| `unsigned` | `->unsigned()` |

---

## 建表模板

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create{TableStudly}Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('{table_snake}', function (Blueprint $table) {
            $table->bigIncrements('id');

            // === 业务字段 ===
            // {根据用户输入生成，每个字段加中文注释}

            // === 审计字段 ===
            $table->bigInteger('_who_added')->unsigned()->nullable(); // 创建人

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('{table_snake}');
    }
}
```

## 改表模板（加字段）

```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Add{ColumnStudly}To{TableStudly}Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('{table_snake}', function (Blueprint $table) {
            $table->{type}('{column}')->{modifiers}; // 中文注释
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('{table_snake}', function (Blueprint $table) {
            $table->dropColumn('{column}');
        });
    }
}
```

## 改表模板（加关联 ID 字段）

当添加的字段是关联其他表的 ID 时（如 `department_id`），使用：

```php
$table->bigInteger('department_id')->unsigned()->nullable(); // 所属科室
```

**禁止**添加 `$table->foreign()` 约束。

---

## 执行步骤

1. **解析参数**：从 `$ARGUMENTS` 提取动作、表名、字段；缺失则交互询问
2. **校验表名**：必须是 snake_case 复数形式，若不是则建议修正
3. **检查冲突**：在 `database/migrations/` 目录搜索是否已有同名迁移
4. **生成文件**：
   - 时间戳：当前时间 `date +%Y_%m_%d_%H%M%S`
   - 写入 `database/migrations/{timestamp}_{action}_{table}.php`
5. **语法检查**：运行 `php -l` 验证生成的文件
6. **输出摘要**：显示文件路径、字段列表、后续操作提示

## 后续操作提示

生成迁移后，提醒用户：
- 运行 `php artisan migrate` 执行迁移
- 如需种子数据，使用 `/db-seeder {table}` 生成
- 如需回滚，使用 `php artisan migrate:rollback`

---

## 示例

### 示例 1：建表
```
/db-migration create treatment_categories
```

交互补充字段后生成：
```php
Schema::create('treatment_categories', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name');                          // 分类名称
    $table->text('description')->nullable();         // 分类描述
    $table->boolean('is_active')->default(true);     // 是否启用
    $table->integer('display_order')->default(0);    // 排序
    $table->bigInteger('_who_added')->unsigned()->nullable(); // 创建人
    $table->timestamps();
    $table->softDeletes();
});
```

### 示例 2：加字段
```
/db-migration alter patients add referral_source:string:nullable
```

生成：
```php
Schema::table('patients', function (Blueprint $table) {
    $table->string('referral_source')->nullable(); // 转介来源
});
```

### 示例 3：加关联字段
```
/db-migration alter appointments add department_id:bigint:unsigned:nullable
```

生成：
```php
Schema::table('appointments', function (Blueprint $table) {
    $table->bigInteger('department_id')->unsigned()->nullable(); // 所属科室
});
```
