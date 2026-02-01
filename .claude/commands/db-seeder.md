---
description: 生成符合项目规范的数据库种子文件
---

# Database Seeder Generator

根据用户描述，生成符合本项目规范的 Laravel 数据库种子文件。

## 输入格式

用户可通过 `$ARGUMENTS` 传入参数，支持以下格式：

```
/db-seeder                                → 交互式：询问表名、数据内容
/db-seeder treatment_categories           → 指定表名，交互补充数据
/db-seeder treatment_categories --register → 生成并注册到 DatabaseSeeder
```

若 `$ARGUMENTS` 为空，进入交互式引导，依次询问：
1. 表名（对应已有迁移）
2. 数据内容（字段和值）
3. 是否注册到 DatabaseSeeder

---

## 项目种子规范（必须遵循）

### 文件约定
- **目录**：`database/seeds/`（本项目是 Laravel 5.8，无命名空间，classmap 自动加载）
- **类名**：大驼峰，如 `TreatmentCategoriesSeeder`
- **文件名**：与类名一致，如 `TreatmentCategoriesSeeder.php`

### 代码约定
- **重跑安全**：`DB::table('xxx')->truncate()` 确保可重复执行
- **审计字段**：`$userId = DB::table('users')->first()->id ?? 1` 动态获取
- **进度提示**：`$this->command->info('Seeding xxx...')` 开头 + `$this->command->info('✓ ...')` 结尾
- **插入方式**：数据定义为数组 + `foreach` 逐条 `DB::table()->insert()`
- **手动时间戳**：每条数据包含 `'created_at' => now(), 'updated_at' => now()`
- **不使用 Model**：统一用 `DB::table()` facade，避免 Model 依赖
- **不使用 mass insert**：逐条插入，确保每条都有独立的审计字段

### 禁止事项
- 禁止硬编码 user ID（如 `'_who_added' => 1`），必须用 `$userId` 变量
- 禁止使用 `DB::table()->insert($allData)` 批量插入（无法逐条设置时间戳）
- 禁止省略 `truncate()`（除非明确说明是追加数据）

---

## Seeder 模板

```php
<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {SeederName} extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 清空已有数据，确保可重复执行
        DB::table('{table_name}')->truncate();

        // 获取审计用户
        $userId = DB::table('users')->first()->id ?? 1;

        $this->command->info('Seeding {table_name}...');

        $data = [
            [
                // {根据用户输入生成具体字段和值}
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ... 更多数据
        ];

        foreach ($data as $item) {
            DB::table('{table_name}')->insert($item);
        }

        $this->command->info('✓ {table_name} seeded (' . count($data) . ' records)');
    }
}
```

---

## DatabaseSeeder 注册规则

当用户指定 `--register` 时，需要在 `database/seeds/DatabaseSeeder.php` 中添加 `$this->call()` 调用。

### 分组结构（参考现有 DatabaseSeeder）

```
[1/5] 基础配置    → BranchesTableSeeder, RolesTableSeeder, PermissionsTableSeeder
[2/5] 用户数据    → UsersTableSeeder
[3/5] 角色权限    → DefaultRolePermissionsSeeder
[4/5] 业务基础数据 → MedicalServicesSeeder, ExpenseCategoriesSeeder, ...
[5/5] 辅助数据    → LeaveTypesTableSeeder, HolidaysTableSeeder, PatientTagsSeeder, MedicalTemplatesSeeder
```

### 插入规则
- 根据新 seeder 的数据性质，判断属于哪个分组
- 在对应分组的末尾添加 `$this->call(XxxSeeder::class);`
- 添加中文注释说明
- 如果新增了分组，更新步骤编号（如 `[1/5]` → `[1/6]`）

### 注册示例

```php
// ========== 4. 业务基础数据 ==========
$this->command->info('[4/5] 初始化业务基础数据...');

// 诊疗服务项目
$this->call(MedicalServicesSeeder::class);

// ... 已有 seeder ...

// 治疗分类 (新增)
$this->call(TreatmentCategoriesSeeder::class);
```

---

## 执行步骤

1. **解析参数**：从 `$ARGUMENTS` 提取表名和选项；缺失则交互询问
2. **校验表名**：确认 `database/migrations/` 中有对应表的迁移文件
3. **检查冲突**：在 `database/seeds/` 搜索是否已有同名 seeder
   - 若已存在，询问用户：覆盖 / 创建新版本 / 取消
4. **交互获取数据**：询问用户每条数据的具体字段和值
5. **生成文件**：写入 `database/seeds/{SeederName}.php`
6. **注册（可选）**：若指定 `--register`，更新 `DatabaseSeeder.php`
7. **语法检查**：运行 `php -l` 验证所有修改的文件
8. **输出摘要**：显示文件路径、数据条数、后续操作提示

## 后续操作提示

生成 seeder 后，提醒用户：
- 运行 `composer dump-autoload` 刷新类映射
- 运行 `php artisan db:seed --class=XxxSeeder` 执行单个 seeder
- 或运行 `php artisan db:seed` 执行所有 seeder（注意会重跑 truncate）

---

## 示例

### 示例 1：生成基础数据 seeder
```
/db-seeder treatment_categories --register
```

生成：
```php
<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentCategoriesSeeder extends Seeder
{
    public function run()
    {
        DB::table('treatment_categories')->truncate();

        $userId = DB::table('users')->first()->id ?? 1;

        $this->command->info('Seeding treatment_categories...');

        $data = [
            [
                'name' => '口腔外科',
                'description' => '拔牙、种植等外科手术',
                'is_active' => true,
                'display_order' => 1,
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '口腔内科',
                'description' => '根管治疗、充填等',
                'is_active' => true,
                'display_order' => 2,
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $item) {
            DB::table('treatment_categories')->insert($item);
        }

        $this->command->info('✓ treatment_categories seeded (' . count($data) . ' records)');
    }
}
```

### 示例 2：更新已有 seeder 的数据
```
/db-seeder quick_phrase_categories
```

若 seeder 已存在，提示用户选择操作，然后根据选择覆盖或创建新版本。
