# Laravel 版本升级方案：5.8 → 11.x

## 一、升级策略选择

### 方案对比

| 策略 | 说明 | 适用场景 |
|---|---|---|
| **逐版本升级** | 5.8→6→7→8→9→10→11，每步修复 breaking changes | 项目依赖多、逻辑复杂 |
| **跳版本升级** | 5.8→8→11，跳过中间版本 | 项目简单、依赖少 |
| **新项目迁移** | 新建 Laravel 11 项目，逐模块搬迁代码 | 想同时重构架构 |

### 推荐：逐版本升级

理由：
- 项目有 96 个控制器、92 个模型、135 张表，体量大
- 每步的 breaking changes 是确定的、可控的
- Laravel 官方为每个大版本提供了升级指南
- 出问题可以精确定位到哪个版本变更导致的

### 目标版本：Laravel 10.x（LTS，2025 年 2 月前安全支持）

不直接升到 11.x 的理由：
- Laravel 10 是最后一个支持传统目录结构的版本
- Laravel 11 大幅简化了骨架结构（合并 Kernel、移除大量 Service Provider），改动量翻倍
- 先稳定在 10.x，后续视情况升到 11.x

---

## 二、当前代码兼容性审计

### 2.1 好消息：没有使用已废弃的全局 helper

以下 Laravel 6.0 移除的函数在本项目中 **未使用**（搜索确认）：

```
str_slug / str_contains / str_random / camel_case / snake_case / studly_case
array_get / array_has / array_first / array_last / starts_with / ends_with
Input:: facade
```

这意味着 **5.8 → 6.0 的升级几乎没有业务代码改动**。

### 2.2 需要处理的 Breaking Changes 清单

| 问题 | 涉及版本 | 影响文件数 | 严重性 |
|---|---|---|---|
| `maatwebsite/excel` 2.1 API 完全重写 | 6.0+ | 7 个控制器 | **致命** |
| `jimmyjs/laravel-report-generator` 停更 | 6.0+ | 3 个控制器 | **致命** |
| `maddhatter/laravel-fullcalendar` 停更 | 6.0+ | 2 个控制器 | **高** |
| `Exception` → `Throwable` 类型声明 | 8.0 | 1 文件 | 低 |
| Model Factory 语法重写 | 8.0 | 1 文件 | 中 |
| Seeder 目录 + 命名空间 | 8.0 | 16 文件 | 中 |
| `fzaninotto/faker` 被弃用 | 8.0 | dev 依赖 | 低 |
| `facade/ignition` 被替换 | 8.0 | dev 依赖 | 低 |
| PHP 版本要求提升 | 9.0+ | composer.json | 环境 |

---

## 三、分步升级详细方案

### Step 0：升级前准备

```bash
# 1. 创建升级分支
git checkout -b upgrade/laravel-10

# 2. 确保当前代码可正常运行
php artisan serve   # 验证所有页面
php artisan migrate:fresh --seed  # 验证迁移和种子

# 3. 记录当前 composer.lock 版本快照
cp composer.lock composer.lock.backup
```

---

### Step 1：5.8 → 6.x

**Laravel 变更**：
- 移除 `str_*` / `array_*` 全局 helper（✓ 本项目未使用，无影响）
- `Illuminate\Support\Facades\Input` 移除（✓ 本项目未使用）
- `Carbon 1.x` → `Carbon 2.x`（向后兼容）

**composer.json 修改**：
```json
{
    "require": {
        "php": "^7.2",
        "laravel/framework": "^6.0",
        "laravel/ui": "^1.0",
        "fideloper/proxy": "^4.0",
        "laravel/tinker": "^2.0"
    },
    "require-dev": {
        "facade/ignition": "^1.4",
        "fzaninotto/faker": "^1.4",
        "mockery/mockery": "^1.0",
        "nunomaduro/collision": "^3.0",
        "phpunit/phpunit": "^8.0"
    }
}
```

**代码修改**：无业务代码修改。

**依赖包兼容**：
| 包 | 操作 |
|---|---|
| `yajra/datatables` 9.7 | 兼容，无需改 |
| `nwidart/laravel-modules` 6.1 | 兼容 |
| `owen-it/laravel-auditing` 10.0 | 兼容 |
| `spatie/laravel-backup` 6.11 | 兼容 |
| `barryvdh/laravel-dompdf` 0.8 | 兼容 |
| `maatwebsite/excel` 2.1 | **暂不升级**，6.x 仍支持 |
| `consoletvs/charts` 6.x | 兼容 |

**验证项**：
- [ ] `php artisan serve` 所有页面可访问
- [ ] 患者/预约/发票 CRUD 正常
- [ ] DataTables 加载正常
- [ ] Excel/PDF 导出正常

---

### Step 2：6.x → 7.x

**Laravel 变更**：
- 路由缓存改进（无代码影响）
- `Blade::component()` 新语法（不影响现有 `@extends` / `@section`）
- HTTP Client 引入（可选使用，不影响现有 Guzzle）

**composer.json 修改**：
```json
{
    "require": {
        "php": "^7.2.5",
        "laravel/framework": "^7.0",
        "laravel/ui": "^2.0",
        "laravel/tinker": "^2.0"
    },
    "require-dev": {
        "facade/ignition": "^2.0",
        "fzaninotto/faker": "^1.9.1",
        "mockery/mockery": "^1.3.1",
        "nunomaduro/collision": "^4.1",
        "phpunit/phpunit": "^8.5"
    }
}
```

**代码修改**：无业务代码修改。

**验证项**：同 Step 1。

---

### Step 3：7.x → 8.x （改动最大的一步）

**Laravel 变更**：
- Model Factory 重写为类（影响 1 文件）
- Seeder 需要命名空间 + 目录改名（影响 16 文件）
- `Exception` → `Throwable`（影响 Handler.php）
- `fzaninotto/faker` → `fakerphp/faker`
- `facade/ignition` → `spatie/laravel-ignition`
- Job Batching / Rate Limiting 等新特性（可选）

**composer.json 修改**：
```json
{
    "require": {
        "php": "^7.3|^8.0",
        "laravel/framework": "^8.0",
        "laravel/ui": "^3.0",
        "laravel/tinker": "^2.5",
        "guzzlehttp/guzzle": "^7.0.1"
    },
    "require-dev": {
        "spatie/laravel-ignition": "^1.0",
        "fakerphp/faker": "^1.9.1",
        "mockery/mockery": "^1.4.4",
        "nunomaduro/collision": "^5.10",
        "phpunit/phpunit": "^9.5.10"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "Modules/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    }
}
```

**代码修改**：

#### 3a. Exception Handler

```php
// app/Exceptions/Handler.php
// 修改前:
use Exception;
public function report(Exception $exception)
public function render($request, Exception $exception)

// 修改后:
use Throwable;
public function report(Throwable $exception)
public function render($request, Throwable $exception)
```

#### 3b. Seeder 迁移

```bash
# 1. 重命名目录
mv database/seeds database/seeders

# 2. 每个 seeder 文件添加命名空间
```

每个 seeder 文件头部添加：
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// ... 其他 use 语句
```

涉及 16 个文件：
- `DatabaseSeeder.php`
- `BranchesTableSeeder.php`
- `UsersTableSeeder.php`
- `RolesTableSeeder.php`
- `PermissionsTableSeeder.php`
- `DefaultRolePermissionsSeeder.php`
- `ChartOfAccountsTableSeeder.php`
- `ExpenseCategoriesSeeder.php`
- `HolidaysTableSeeder.php`
- `InsuranceCompaniesTableSeeder.php`
- `InventoryCategoriesSeeder.php`
- `LeaveTypesTableSeeder.php`
- `MedicalServicesSeeder.php`
- `MedicalTemplatesSeeder.php`
- `PatientTagsSeeder.php`
- `PatientsTableSeeder.php`

#### 3c. Model Factory 重写

```php
// 修改前: database/factories/UserFactory.php
$factory->define(User::class, function (Faker $faker) {
    return [
        'surname' => $faker->lastName,
        'othername' => $faker->firstName,
        // ...
    ];
});

// 修改后: database/factories/UserFactory.php
namespace Database\Factories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'surname' => $this->faker->lastName,
            'othername' => $this->faker->firstName,
            'email' => $this->faker->unique()->safeEmail,
            'phone_no' => $this->faker->phoneNumber,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ];
    }
}
```

#### 3d. 依赖包同步升级

| 包 | 修改 |
|---|---|
| `maatwebsite/excel` | **此时必须升级到 3.1**（见下方独立章节） |
| `jimmyjs/laravel-report-generator` | **移除，用 maatwebsite/excel 3.x 替代** |
| `maddhatter/laravel-fullcalendar` | **移除，直接引入 FullCalendar.js** |
| `nwidart/laravel-modules` | 升级到 `^8.0` |
| `owen-it/laravel-auditing` | 升级到 `^12.0` |
| `spatie/laravel-backup` | 升级到 `^7.0` |
| `yajra/datatables` | 升级到 `^10.0` |
| `barryvdh/laravel-dompdf` | 升级到 `^1.0` |
| `consoletvs/charts` | 升级到 `^6.*`（或替换） |
| `fideloper/proxy` | **移除**（Laravel 8+ 内置 TrustProxies） |
| `thomasjohnkane/snooze` | 检查兼容性 |

---

### Step 4：8.x → 9.x

**前提**：PHP ≥ 8.0

**Laravel 变更**：
- 匿名 Stub Migration 成为默认（不影响现有命名迁移）
- `Illuminate\Support\Facades\Route::home()` 移除（本项目未用）
- Controller 路由组简化（可选，不强制）

**composer.json 修改**：
```json
{
    "require": {
        "php": "^8.0.2",
        "laravel/framework": "^9.0"
    },
    "require-dev": {
        "spatie/laravel-ignition": "^1.0",
        "nunomaduro/collision": "^6.1",
        "phpunit/phpunit": "^9.5.10"
    }
}
```

**代码修改**：

```php
// app/Providers/RouteServiceProvider.php
// 确认 HOME 常量存在
public const HOME = '/home';
```

**依赖包同步**：
| 包 | 版本 |
|---|---|
| `nwidart/laravel-modules` | `^9.0` |
| `owen-it/laravel-auditing` | `^13.0` |
| `spatie/laravel-backup` | `^8.0` |

---

### Step 5：9.x → 10.x （目标版本）

**前提**：PHP ≥ 8.1

**Laravel 变更**：
- 最低 PHP 8.1
- `Kernel` 中间件组无变化
- `Process` facade 新增（可选）
- Laravel Pennant 特性标记（可选）

**composer.json 修改**：
```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "laravel/tinker": "^2.8"
    },
    "require-dev": {
        "spatie/laravel-ignition": "^2.0",
        "fakerphp/faker": "^1.9.1",
        "mockery/mockery": "^1.4.4",
        "nunomaduro/collision": "^7.0",
        "phpunit/phpunit": "^10.1"
    }
}
```

**依赖包同步**：
| 包 | 版本 |
|---|---|
| `nwidart/laravel-modules` | `^10.0` |
| `yajra/datatables` | `^10.0` 或 `^11.0` |
| `spatie/laravel-backup` | `^8.0` |

---

## 四、停更包替代方案

### 4.1 `maatwebsite/excel` 2.1 → 3.1（7 个控制器）

这是升级中工作量最大的部分。

**旧 API 模式**（本项目使用）：
```php
Excel::create($filename, function ($excel) {
    $excel->sheet('Sheet1', function ($sheet) {
        $sheet->cell('A1', function ($cell) {
            $cell->setValue('Total');
            $cell->setFontWeight('bold');
        });
        $sheet->fromArray($data);
    });
})->download('xlsx');
```

**新 API 模式**（3.x）：
```php
// 创建 Export 类
class InvoiceExport implements FromArray, WithHeadings, WithStyles
{
    private $data;
    private $grandTotal;

    public function __construct(array $data, float $grandTotal)
    {
        $this->data = $data;
        $this->grandTotal = $grandTotal;
    }

    public function array(): array
    {
        // 添加合计行
        $this->data[] = ['', '', '', 'Total= ' . number_format($this->grandTotal)];
        return $this->data;
    }

    public function headings(): array
    {
        return ['Patient', 'Invoice No', 'Date', 'Amount'];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 1;
        return [$lastRow => ['font' => ['bold' => true]]];
    }
}

// Controller 中使用
return Excel::download(new InvoiceExport($data, $total), $filename . '.xlsx');
```

**需要创建的 Export 类**（建议放在 `App/Exports/`）：

| Export 类 | 对应控制器 | 说明 |
|---|---|---|
| `InvoiceExport.php` | InvoiceController | 发票导出 |
| `DebtorsExport.php` | DebtorsReportController | 欠费报表 |
| `DoctorPerformanceExport.php` | DoctorPerformanceReport | 医生绩效 |
| `ProceduresExport.php` | ProceduresReportController | 项目收入 |
| `InvoicingReportExport.php` | InvoicingReportsController | 综合财务 |
| `ExpenseExport.php` | ExpenseController | 费用导出 |
| `BudgetLineExport.php` | BudgetLineReportController | 预算分析 |

### 4.2 `jimmyjs/laravel-report-generator` → 移除（3 个控制器）

用 `maatwebsite/excel` 3.x 的 `FromQuery` 接口替代：

```php
// 旧代码 (ExcelReport::of)
return ExcelReport::of(null, $meta, $queryBuilder, $columns)
    ->simple()
    ->download('patients' . date('Y-m-d'));

// 新代码
class PatientExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    private $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Patient No', 'Surname', 'Other Name', 'Phone', 'Email'];
    }

    public function map($patient): array
    {
        return [
            $patient->patient_no,
            $patient->surname,
            $patient->othername,
            $patient->phone_no,
            $patient->email,
        ];
    }
}

// Controller
return Excel::download(new PatientExport($queryBuilder), 'patients.xlsx');
```

涉及控制器：
- `PatientController::export()`
- `AppointmentsController::export()`
- `SmsLoggingController::export()`

### 4.3 `maddhatter/laravel-fullcalendar` → 直接使用 FullCalendar.js（2 处）

**旧模式**（后端生成日历 HTML）：
```php
// Controller
$incoming[] = Calendar::event($title, false, $start, $end, null, ['textColor' => '#fff']);
$calendar = Calendar::addEvents($incoming)->setOptions([...]);
return view('appointments.index', compact('calendar'));

// Blade
{!! $calendar->calendar() !!}
{!! $calendar->script() !!}
```

**新模式**（前端 FullCalendar 5.x + JSON API）：
```javascript
// Blade 中直接使用 FullCalendar
document.addEventListener('DOMContentLoaded', function() {
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        locale: '{{ app()->getLocale() === "zh-CN" ? "zh-cn" : "en" }}',
        events: '/api/appointments/calendar',  // JSON 接口
        eventClick: function(info) { /* ... */ }
    });
    calendar.render();
});
```

```php
// 新增 API 端点
public function calendarEvents(Request $request)
{
    $appointments = DB::table('appointments')
        ->join('patients', 'patients.id', 'appointments.patient_id')
        ->whereBetween('appointments.sort_by', [$request->start, $request->end])
        ->get();

    return response()->json($appointments->map(function ($apt) {
        return [
            'id' => $apt->id,
            'title' => NameHelper::join($apt->surname, $apt->othername),
            'start' => $apt->sort_by,
            'textColor' => '#ffffff',
        ];
    }));
}
```

涉及文件：
- `App/Http/Controllers/AppointmentsController.php`（主日历）
- `Modules/Doctor/Http/Controllers/AppointmentsController.php`（医生日历）
- `resources/views/appointments/index.blade.php`（视图）
- `Modules/Doctor/Resources/views/appointments/index.blade.php`（视图）

---

## 五、PHP 版本升级路径

| Laravel 版本 | PHP 最低版本 | 推荐 PHP 版本 |
|---|---|---|
| 6.x | 7.2 | 7.4 |
| 7.x | 7.2.5 | 7.4 |
| 8.x | 7.3 | 8.0 |
| 9.x | **8.0.2** | 8.1 |
| 10.x | **8.1** | 8.2 |

**建议**：
- Step 1-2（Laravel 6-7）：保持 PHP 7.4
- Step 3（Laravel 8）：升级到 PHP 8.0
- Step 4-5（Laravel 9-10）：升级到 PHP 8.1+

---

## 六、执行计划

### 执行顺序与依赖

```
准备阶段
├── 建立升级分支
├── 搭建测试环境（Docker）
└── 编写冒烟测试脚本
     │
     ▼
Step 1: 5.8 → 6.x ──── 改动量: 极小（仅 composer.json）
     │
     ▼
Step 2: 6.x → 7.x ──── 改动量: 极小（仅 composer.json）
     │
     ▼
停更包替代（可与 Step 3 并行）
├── 重写 7 个 Excel 导出（→ Export 类）
├── 替代 3 个 ExcelReport 调用
└── 替换 2 处 Calendar 为前端 FullCalendar
     │
     ▼
Step 3: 7.x → 8.x ──── 改动量: 大
├── Exception → Throwable
├── Seeder 迁移（16 文件）
├── Factory 重写（1 文件）
├── 依赖包批量升级
└── PHP 升级到 8.0
     │
     ▼
Step 4: 8.x → 9.x ──── 改动量: 小
     │
     ▼
Step 5: 9.x → 10.x ─── 改动量: 小（PHP 8.1+）
     │
     ▼
验证 & 合并到 master
```

### 每步必做的验证清单

```
□ composer update 无报错
□ php artisan serve 启动正常
□ 登录/登出正常
□ 5 个角色首页可访问
□ 患者列表（DataTables 加载）
□ 患者新增/编辑/删除
□ 预约日历显示
□ 预约新增/编辑
□ 发票创建 → 支付 → 退费
□ Excel 导出（发票、患者、预约）
□ PDF 打印（发票、退费单）
□ 中英文切换
□ 会员充值/消费
□ 病例创建/编辑
□ 牙位图交互
```

---

## 七、风险与回退策略

| 风险 | 概率 | 影响 | 应对措施 |
|---|---|---|---|
| 某第三方包不兼容目标 Laravel 版本 | 中 | 阻塞 | 找替代包或 fork 修复 |
| Excel 导出重写引入逻辑错误 | 中 | 数据错误 | 逐个导出对比旧版输出 |
| 隐式行为变化导致线上 bug | 低 | 功能异常 | 完善冒烟测试覆盖关键路径 |
| PHP 8.x 与旧代码不兼容 | 低 | 运行时错误 | PHP 8 静态分析工具 phpstan |

**回退方式**：每个 Step 完成后打 Git Tag，出现不可解决问题时 `git checkout` 到上一个 Tag。

```bash
git tag v-laravel-5.8   # 升级前
git tag v-laravel-6.x   # Step 1 完成
git tag v-laravel-7.x   # Step 2 完成
git tag v-laravel-8.x   # Step 3 完成
git tag v-laravel-9.x   # Step 4 完成
git tag v-laravel-10.x  # Step 5 完成（目标）
```
