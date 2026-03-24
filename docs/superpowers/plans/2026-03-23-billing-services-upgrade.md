# 收费项目管理升级 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 升级现有 clinic-services 页面，增加收费大类树形分类（service_categories）、套餐管理（service_packages），并将 medical_services 表的旧 `category` varchar 字段迁移为规范化 FK。

**Architecture:** 新建 3 张表（service_categories / service_packages / service_package_items），修改 medical_services 加 4 个新字段（category_id / is_discountable / is_favorite / sort_order），旧 `category` 数据自动回填后废弃。控制器 → Service 层 → Model，DataTable 服务端处理，Blade 只保留 HTML，JS/CSS 分离到 public/。

**Tech Stack:** PHP 8.2, Laravel 11, MySQL, Yajra DataTables, Maatwebsite/Excel 3.1, Bootstrap 4, jQuery, Select2

**Spec 文档:** `docs/superpowers/specs/2026-03-23-billing-sterilization-design.md` §二~§九（收费项目部分）

---

## File Map

### 新建文件

| 文件 | 职责 |
|------|------|
| `database/migrations/2026_03_23_000001_create_service_categories_table.php` | service_categories 表 |
| `database/migrations/2026_03_23_000002_add_category_fields_to_medical_services_table.php` | medical_services 加字段 + 旧 category 数据回填 |
| `database/migrations/2026_03_23_000003_create_service_packages_tables.php` | service_packages + service_package_items 表 |
| `App/ServiceCategory.php` | 大类模型 |
| `App/ServicePackage.php` | 套餐模型 |
| `App/ServicePackageItem.php` | 套餐明细模型 |
| `App/Services/ServiceCategoryService.php` | 大类 CRUD + 树形数据组装 |
| `App/Services/ServicePackageService.php` | 套餐 CRUD（先删后插明细） |
| `App/Http/Controllers/ServiceCategoryController.php` | 大类 CRUD + 排序 API |
| `App/Http/Controllers/ServicePackageController.php` | 套餐 CRUD |
| `resources/views/clinic_services/index.blade.php` | 主页面（全面改写） |
| `resources/views/clinic_services/_tab_services.blade.php` | 项目管理 Tab（左树 + 右表） |
| `resources/views/clinic_services/_tab_packages.blade.php` | 套餐管理 Tab |
| `resources/views/clinic_services/_modal_service.blade.php` | 新增/编辑项目弹框 |
| `resources/views/clinic_services/_modal_package.blade.php` | 新增/编辑套餐弹框 |
| `resources/views/clinic_services/_modal_import.blade.php` | Excel 导入弹框 |
| `public/include_js/clinic_services.js` | 页面所有 JS 逻辑 |
| `public/css/clinic_services.css` | 页面样式 |
| `resources/lang/zh-CN/clinic_services.php` | 新增翻译键 |
| `resources/lang/en/clinic_services.php` | 英文翻译键（内容同中文，英文表达） |
| `tests/Feature/ServiceCategoryTest.php` | 大类 CRUD 功能测试 |
| `tests/Feature/ServicePackageTest.php` | 套餐 CRUD 功能测试 |

### 修改文件

| 文件 | 修改内容 |
|------|---------|
| `App/MedicalService.php` | fillable 加 4 新字段，casts 加 is_discountable/is_favorite，加 category() 关联 |
| `App/Services/MedicalServiceService.php` | createService/updateService 支持新字段，新增 batchUpdatePrice / importFromExcel / getExportData |
| `App/Http/Controllers/MedicalServiceController.php` | index 加 category_id 列，新增 batchUpdatePrice / import / export 方法 |
| `App/Services/InvoiceService.php` | getServiceCategoryTree() 改为 JOIN service_categories |
| `database/seeders/PermissionsTableSeeder.php` | 新增 3 条权限 |
| `database/seeders/MenuItemsSeeder.php` | seedClinicalCenter 中加 service-categories 路由 |
| `routes/web.php` | 新增 admin/service-categories、service-packages 路由及 clinic-services 扩展路由 |
| `resources/lang/zh-CN/menu.php` | 无变更（本期收费管理沿用现有菜单项） |

---

## Task 1: 数据库迁移 — 创建 service_categories 表

**Files:**
- Create: `database/migrations/2026_03_23_000001_create_service_categories_table.php`

- [ ] **Step 1: 写迁移文件**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('_who_added')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
```

- [ ] **Step 2: 运行迁移**

```bash
php artisan migrate --path=database/migrations/2026_03_23_000001_create_service_categories_table.php
```

期望输出：`Migrating: 2026_03_23_000001_create_service_categories_table` → `Migrated`

---

## Task 2: 数据库迁移 — medical_services 加字段 + 旧 category 数据回填

**Files:**
- Create: `database/migrations/2026_03_23_000002_add_category_fields_to_medical_services_table.php`

- [ ] **Step 1: 写迁移文件**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_services', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')
                ->constrained('service_categories')->nullOnDelete();
            $table->boolean('is_discountable')->default(true)->after('is_active');
            $table->boolean('is_favorite')->default(false)->after('is_discountable');
            $table->integer('sort_order')->default(0)->after('is_favorite');
        });

        // 回填旧 category varchar → service_categories + category_id FK
        $categories = DB::table('medical_services')
            ->whereNull('deleted_at')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->distinct()
            ->pluck('category');

        foreach ($categories as $catName) {
            $catId = DB::table('service_categories')->insertGetId([
                'name'       => $catName,
                'sort_order' => 0,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('medical_services')
                ->where('category', $catName)
                ->whereNull('deleted_at')
                ->update(['category_id' => $catId]);
        }
    }

    public function down(): void
    {
        Schema::table('medical_services', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'is_discountable', 'is_favorite', 'sort_order']);
        });
    }
};
```

- [ ] **Step 2: 运行迁移**

```bash
php artisan migrate --path=database/migrations/2026_03_23_000002_add_category_fields_to_medical_services_table.php
```

- [ ] **Step 3: 验证回填结果**

```bash
php artisan tinker --execute="
echo 'service_categories count: ' . DB::table('service_categories')->count() . PHP_EOL;
echo 'medical_services with category_id: ' . DB::table('medical_services')->whereNotNull('category_id')->count() . PHP_EOL;
echo 'medical_services with old category: ' . DB::table('medical_services')->whereNotNull('category')->where('category','!=','')->count() . PHP_EOL;
"
```

期望：两个非零数相同（每条有旧 category 的记录都回填了 category_id）。

- [ ] **Step 4: 提交**

```bash
git add database/migrations/2026_03_23_000001_create_service_categories_table.php
git add database/migrations/2026_03_23_000002_add_category_fields_to_medical_services_table.php
git commit -m "feat(db): add service_categories table and upgrade medical_services fields"
```

---

## Task 3: 数据库迁移 — service_packages + service_package_items

**Files:**
- Create: `database/migrations/2026_03_23_000003_create_service_packages_tables.php`

- [ ] **Step 1: 写迁移文件**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('total_price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->foreignId('_who_added')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('service_packages')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('medical_services')->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->decimal('price', 12, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_items');
        Schema::dropIfExists('service_packages');
    }
};
```

- [ ] **Step 2: 运行迁移**

```bash
php artisan migrate --path=database/migrations/2026_03_23_000003_create_service_packages_tables.php
```

- [ ] **Step 3: 提交**

```bash
git add database/migrations/2026_03_23_000003_create_service_packages_tables.php
git commit -m "feat(db): add service_packages and service_package_items tables"
```

---

## Task 4: 模型 — ServiceCategory / ServicePackage / ServicePackageItem

**Files:**
- Create: `App/ServiceCategory.php`
- Create: `App/ServicePackage.php`
- Create: `App/ServicePackageItem.php`
- Modify: `App/MedicalService.php`

- [ ] **Step 1: 创建 ServiceCategory 模型**

```php
<?php
// App/ServiceCategory.php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'sort_order', 'is_active', '_who_added'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function services()
    {
        return $this->hasMany(MedicalService::class, 'category_id');
    }
}
```

- [ ] **Step 2: 创建 ServicePackage 模型**

```php
<?php
// App/ServicePackage.php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackage extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'total_price', 'is_active', '_who_added'];

    protected $casts = [
        'is_active'   => 'boolean',
        'total_price' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(ServicePackageItem::class, 'package_id')->orderBy('sort_order');
    }
}
```

- [ ] **Step 3: 创建 ServicePackageItem 模型**

```php
<?php
// App/ServicePackageItem.php
namespace App;

use Illuminate\Database\Eloquent\Model;

class ServicePackageItem extends Model
{
    protected $fillable = ['package_id', 'service_id', 'qty', 'price', 'sort_order'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(MedicalService::class, 'service_id');
    }
}
```

- [ ] **Step 4: 更新 MedicalService 模型**

在 `App/MedicalService.php`：

- `$fillable` 加入：`'category_id', 'is_discountable', 'is_favorite', 'sort_order'`
- `$casts` 加入：`'is_discountable' => 'boolean', 'is_favorite' => 'boolean'`
- 新增关联方法：

```php
public function category()
{
    return $this->belongsTo(ServiceCategory::class, 'category_id');
}
```

- [ ] **Step 5: 在 tinker 中验证模型**

```bash
php artisan tinker --execute="
use App\ServiceCategory;
use App\MedicalService;
echo ServiceCategory::count() . ' categories' . PHP_EOL;
echo MedicalService::whereNotNull('category_id')->count() . ' services with category' . PHP_EOL;
"
```

- [ ] **Step 6: 提交**

```bash
git add App/ServiceCategory.php App/ServicePackage.php App/ServicePackageItem.php App/MedicalService.php
git commit -m "feat(model): add ServiceCategory, ServicePackage, ServicePackageItem models; update MedicalService"
```

---

## Task 5: ServiceCategoryService + ServiceCategoryController

**Files:**
- Create: `App/Services/ServiceCategoryService.php`
- Create: `App/Http/Controllers/ServiceCategoryController.php`
- Create: `tests/Feature/ServiceCategoryTest.php`

- [ ] **Step 1: 写失败测试**

```php
<?php
// tests/Feature/ServiceCategoryTest.php
namespace Tests\Feature;

use App\ServiceCategory;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['is_doctor' => 'super-admin']);
    }

    public function test_can_list_categories(): void
    {
        ServiceCategory::create(['name' => '正畸', 'sort_order' => 1, 'is_active' => true]);
        $this->actingAs($this->adminUser())
            ->getJson('/admin/service-categories')
            ->assertOk()
            ->assertJsonFragment(['name' => '正畸']);
    }

    public function test_can_create_category(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson('/admin/service-categories', ['name' => '种植', 'sort_order' => 2])
            ->assertOk()
            ->assertJson(['status' => 1]);
        $this->assertDatabaseHas('service_categories', ['name' => '种植']);
    }

    public function test_cannot_create_duplicate_name(): void
    {
        ServiceCategory::create(['name' => '正畸']);
        $this->actingAs($this->adminUser())
            ->postJson('/admin/service-categories', ['name' => '正畸'])
            ->assertOk()
            ->assertJson(['status' => 0]);
    }

    public function test_can_reorder_categories(): void
    {
        $a = ServiceCategory::create(['name' => 'A', 'sort_order' => 1]);
        $b = ServiceCategory::create(['name' => 'B', 'sort_order' => 2]);
        $this->actingAs($this->adminUser())
            ->postJson('/admin/service-categories/reorder', [
                'order' => [$b->id, $a->id],
            ])
            ->assertOk()
            ->assertJson(['status' => 1]);
        $this->assertEquals(1, ServiceCategory::find($b->id)->sort_order);
        $this->assertEquals(2, ServiceCategory::find($a->id)->sort_order);
    }
}
```

- [ ] **Step 2: 跑测试，确认失败**

```bash
php artisan test tests/Feature/ServiceCategoryTest.php
```

期望：FAIL（路由/控制器不存在）

- [ ] **Step 3: 创建 ServiceCategoryService**

```php
<?php
// App/Services/ServiceCategoryService.php
namespace App\Services;

use App\ServiceCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ServiceCategoryService
{
    private const CACHE_KEY = 'service_category_list';
    private const CACHE_TTL = 3600 * 6;

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return ServiceCategory::whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order', 'is_active']);
    }

    public function create(array $data): ServiceCategory
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
        return ServiceCategory::create([
            'name'       => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => $data['is_active'] ?? true,
            '_who_added' => Auth::id(),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
        return (bool) ServiceCategory::where('id', $id)->update([
            'name'       => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => $data['is_active'] ?? true,
        ]);
    }

    public function delete(int $id): bool
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
        return (bool) ServiceCategory::where('id', $id)->delete();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $pos => $id) {
            ServiceCategory::where('id', $id)->update(['sort_order' => $pos + 1]);
        }
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
    }
}
```

- [ ] **Step 4: 创建 ServiceCategoryController**

```php
<?php
// App/Http/Controllers/ServiceCategoryController.php
namespace App\Http\Controllers;

use App\Services\ServiceCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceCategoryController extends Controller
{
    public function __construct(private ServiceCategoryService $service)
    {
        $this->middleware('can:manage-service-categories');
    }

    public function index(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->service->getAll()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:service_categories,name',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->create($request->only(['name', 'sort_order', 'is_active']));
        return response()->json(['status' => 1, 'message' => __('common.created_successfully')]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => "required|string|max:50|unique:service_categories,name,{$id}",
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->update($id, $request->only(['name', 'sort_order', 'is_active']));
        return response()->json(['status' => 1, 'message' => __('common.updated_successfully')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['status' => 1, 'message' => __('common.deleted_successfully')]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), ['order' => 'required|array']);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->reorder($request->input('order'));
        return response()->json(['status' => 1]);
    }
}
```

- [ ] **Step 5: 在 routes/web.php 加路由（暂时用 Route::any 测试，Task 9 统一规整）**

```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('service-categories', 'ServiceCategoryController@index')->name('service-categories.index');
    Route::post('service-categories', 'ServiceCategoryController@store')->name('service-categories.store');
    Route::put('service-categories/{id}', 'ServiceCategoryController@update')->name('service-categories.update');
    Route::delete('service-categories/{id}', 'ServiceCategoryController@destroy')->name('service-categories.destroy');
    Route::post('service-categories/reorder', 'ServiceCategoryController@reorder')->name('service-categories.reorder');
});
```

> **注意：** reorder 路由必须在 `{id}` 路由之前注册，防止 Laravel 将 "reorder" 当作 id。

- [ ] **Step 6: 加 manage-service-categories 权限（暂用 tinker 补，Task 8 统一写入 Seeder）**

```bash
php artisan tinker --execute="
use App\Permission;
use App\Role;
Permission::firstOrCreate(['slug'=>'manage-service-categories'],['name'=>'管理收费大类','module'=>'医疗管理','description'=>'管理收费项目大类']);
\$perm = Permission::where('slug','manage-service-categories')->first();
Role::whereIn('slug',['super-admin','admin'])->each(fn(\$r) => \$r->permissions()->syncWithoutDetaching([\$perm->id]));
echo 'done';
"
```

- [ ] **Step 7: 跑测试，确认通过**

```bash
php artisan test tests/Feature/ServiceCategoryTest.php
```

期望：PASS (4 tests)

- [ ] **Step 8: 提交**

```bash
git add App/Services/ServiceCategoryService.php \
        App/Http/Controllers/ServiceCategoryController.php \
        tests/Feature/ServiceCategoryTest.php \
        routes/web.php
git commit -m "feat(billing): add ServiceCategory CRUD endpoint and service"
```

---

## Task 6: 更新 InvoiceService::getServiceCategoryTree()

**Files:**
- Modify: `App/Services/InvoiceService.php` (lines 515–541)

- [ ] **Step 1: 将旧实现替换为 JOIN service_categories**

将 `getServiceCategoryTree()` 方法体替换为：

```php
public function getServiceCategoryTree(): array
{
    return Cache::remember('billing_service_category_tree', 360 * 60, function () {
        // 有分类的项目：JOIN service_categories 取分类名
        $services = DB::table('medical_services')
            ->leftJoin('service_categories', 'service_categories.id', '=', 'medical_services.category_id')
            ->where('medical_services.is_active', true)
            ->whereNull('medical_services.deleted_at')
            ->orderByRaw('COALESCE(service_categories.sort_order, 9999)')
            ->orderBy('service_categories.name')
            ->orderBy('medical_services.sort_order')
            ->orderBy('medical_services.name')
            ->select([
                'medical_services.id',
                'medical_services.name',
                'medical_services.unit',
                'medical_services.price',
                'medical_services.is_discountable',
                'medical_services.is_favorite',
                \DB::raw("COALESCE(service_categories.name, '" . __('invoices.select_category') . "') as category_label"),
            ])
            ->get();

        $tree = [];
        foreach ($services as $svc) {
            $cat = $svc->category_label;
            if (!isset($tree[$cat])) {
                $tree[$cat] = [];
            }
            $tree[$cat][] = [
                'id'             => $svc->id,
                'name'           => $svc->name,
                'unit'           => $svc->unit ?: '次',
                'price'          => (string) $svc->price,
                'category'       => $cat,
                'is_discountable' => (bool) $svc->is_discountable,
                'is_favorite'    => (bool) $svc->is_favorite,
            ];
        }

        return $tree;
    });
}
```

- [ ] **Step 2: 清缓存并验证**

```bash
php artisan cache:clear
php artisan tinker --execute="
use App\Services\InvoiceService;
\$svc = app(InvoiceService::class);
\$tree = \$svc->getServiceCategoryTree();
echo 'categories: ' . count(\$tree) . PHP_EOL;
echo 'first category: ' . array_key_first(\$tree) . PHP_EOL;
"
```

期望：打印出分类数 ≥ 1，第一个分类名正确。

- [ ] **Step 3: 提交**

```bash
git add App/Services/InvoiceService.php
git commit -m "feat(billing): rewrite getServiceCategoryTree() to join service_categories FK"
```

---

## Task 7: ServicePackageService + ServicePackageController

**Files:**
- Create: `App/Services/ServicePackageService.php`
- Create: `App/Http/Controllers/ServicePackageController.php`
- Create: `tests/Feature/ServicePackageTest.php`

- [ ] **Step 1: 写失败测试**

```php
<?php
// tests/Feature/ServicePackageTest.php
namespace Tests\Feature;

use App\MedicalService;
use App\ServicePackage;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePackageTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['is_doctor' => 'super-admin']);
    }

    private function makeService(string $name = '洗牙', float $price = 100.0): MedicalService
    {
        return MedicalService::create(['name' => $name, 'price' => $price, '_who_added' => 1]);
    }

    public function test_can_create_package_with_items(): void
    {
        $s1 = $this->makeService('洗牙', 100);
        $s2 = $this->makeService('补牙', 200);

        $this->actingAs($this->adminUser())
            ->postJson('/service-packages', [
                'name'        => '基础套餐',
                'total_price' => 250,
                'items'       => [
                    ['service_id' => $s1->id, 'qty' => 1, 'price' => 80],
                    ['service_id' => $s2->id, 'qty' => 1, 'price' => 170],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $pkg = ServicePackage::where('name', '基础套餐')->first();
        $this->assertNotNull($pkg);
        $this->assertCount(2, $pkg->items);
    }

    public function test_update_package_replaces_items(): void
    {
        $s1 = $this->makeService('洗牙', 100);
        $s2 = $this->makeService('补牙', 200);
        $pkg = ServicePackage::create([
            'name' => '旧套餐', 'total_price' => 100, 'is_active' => true,
        ]);
        $pkg->items()->create(['service_id' => $s1->id, 'qty' => 1, 'price' => 100]);

        $this->actingAs($this->adminUser())
            ->putJson("/service-packages/{$pkg->id}", [
                'name'        => '新套餐',
                'total_price' => 200,
                'items'       => [
                    ['service_id' => $s2->id, 'qty' => 2, 'price' => 100],
                ],
            ])
            ->assertOk()->assertJson(['status' => 1]);

        $pkg->refresh();
        $this->assertEquals('新套餐', $pkg->name);
        $this->assertCount(1, $pkg->items);
        $this->assertEquals($s2->id, $pkg->items->first()->service_id);
    }
}
```

- [ ] **Step 2: 跑测试，确认失败**

```bash
php artisan test tests/Feature/ServicePackageTest.php
```

- [ ] **Step 3: 创建 ServicePackageService**

```php
<?php
// App/Services/ServicePackageService.php
namespace App\Services;

use App\ServicePackage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicePackageService
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return ServicePackage::whereNull('deleted_at')
            ->with('items.service')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ServicePackage
    {
        return DB::transaction(function () use ($data) {
            $pkg = ServicePackage::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'total_price' => $data['total_price'],
                'is_active'   => $data['is_active'] ?? true,
                '_who_added'  => Auth::id(),
            ]);
            $this->syncItems($pkg, $data['items'] ?? []);
            return $pkg;
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $pkg = ServicePackage::findOrFail($id);
            $pkg->update([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'total_price' => $data['total_price'],
                'is_active'   => $data['is_active'] ?? true,
            ]);
            $this->syncItems($pkg, $data['items'] ?? []);
            return true;
        });
    }

    public function delete(int $id): bool
    {
        return (bool) ServicePackage::where('id', $id)->delete();
    }

    /** 先删后插套餐明细（物理删除）*/
    private function syncItems(ServicePackage $pkg, array $items): void
    {
        $pkg->items()->delete();
        foreach ($items as $i => $item) {
            $pkg->items()->create([
                'service_id' => $item['service_id'],
                'qty'        => $item['qty'] ?? 1,
                'price'      => $item['price'],
                'sort_order' => $i,
            ]);
        }
    }
}
```

- [ ] **Step 4: 创建 ServicePackageController**

```php
<?php
// App/Http/Controllers/ServicePackageController.php
namespace App\Http\Controllers;

use App\Services\ServicePackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServicePackageController extends Controller
{
    public function __construct(private ServicePackageService $service)
    {
        $this->middleware('can:manage-service-packages');
    }

    public function index(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->service->getAll()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'            => 'required|string|max:100',
            'total_price'     => 'required|numeric|min:0',
            'items'           => 'array',
            'items.*.service_id' => 'required|integer|exists:medical_services,id',
            'items.*.qty'     => 'required|integer|min:1',
            'items.*.price'   => 'required|numeric|min:0',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->create($request->all());
        return response()->json(['status' => 1, 'message' => __('common.created_successfully')]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'            => 'required|string|max:100',
            'total_price'     => 'required|numeric|min:0',
            'items'           => 'array',
            'items.*.service_id' => 'required|integer|exists:medical_services,id',
            'items.*.qty'     => 'required|integer|min:1',
            'items.*.price'   => 'required|numeric|min:0',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->update($id, $request->all());
        return response()->json(['status' => 1, 'message' => __('common.updated_successfully')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['status' => 1, 'message' => __('common.deleted_successfully')]);
    }
}
```

- [ ] **Step 5: 加 Routes + Permission（同 Task 5 的 tinker 做法）**

在 routes/web.php 加：
```php
Route::resource('service-packages', 'ServicePackageController')->only(['index', 'store', 'update', 'destroy']);
```

```bash
php artisan tinker --execute="
use App\Permission; use App\Role;
Permission::firstOrCreate(['slug'=>'manage-service-packages'],['name'=>'管理收费套餐','module'=>'医疗管理','description'=>'管理收费套餐与明细']);
\$perm = Permission::where('slug','manage-service-packages')->first();
Role::whereIn('slug',['super-admin','admin'])->each(fn(\$r) => \$r->permissions()->syncWithoutDetaching([\$perm->id]));
echo 'done';
"
```

- [ ] **Step 6: 跑测试，确认通过**

```bash
php artisan test tests/Feature/ServicePackageTest.php
```

- [ ] **Step 7: 提交**

```bash
git add App/Services/ServicePackageService.php \
        App/Http/Controllers/ServicePackageController.php \
        tests/Feature/ServicePackageTest.php \
        routes/web.php
git commit -m "feat(billing): add ServicePackage CRUD with item sync"
```

---

## Task 8: 升级 MedicalServiceService + MedicalServiceController

**Files:**
- Modify: `App/Services/MedicalServiceService.php`
- Modify: `App/Http/Controllers/MedicalServiceController.php`

- [ ] **Step 1: 更新 getServiceList() 加 category_id join**

在 `MedicalServiceService::getServiceList()` 中加 `service_categories` LEFT JOIN：

```php
public function getServiceList(?string $search, ?int $categoryId = null): Collection
{
    $query = DB::table('medical_services')
        ->leftJoin('users', 'users.id', 'medical_services._who_added')
        ->leftJoin('service_categories', 'service_categories.id', 'medical_services.category_id')
        ->whereNull('medical_services.deleted_at')
        ->select([
            'medical_services.*',
            'users.surname',
            'service_categories.name as category_name',
        ]);

    if ($search) {
        $query->where('medical_services.name', 'like', '%' . $search . '%');
    }
    if ($categoryId) {
        $query->where('medical_services.category_id', $categoryId);
    }

    return $query->orderBy('medical_services.id', 'desc')->get();
}
```

- [ ] **Step 2: 更新 createService() / updateService() 支持新字段**

```php
public function createService(array $data): ?\App\MedicalService
{
    $service = \App\MedicalService::create([
        'name'           => $data['name'],
        'price'          => $data['price'],
        'unit'           => $data['unit'] ?? null,
        'description'    => $data['description'] ?? null,
        'category_id'    => $data['category_id'] ?? null,
        'is_active'      => $data['is_active'] ?? true,
        'is_discountable' => $data['is_discountable'] ?? true,
        'is_favorite'    => $data['is_favorite'] ?? false,
        'sort_order'     => $data['sort_order'] ?? 0,
        '_who_added'     => Auth::id(),
    ]);
    Cache::forget(self::CACHE_KEY_NAMES);
    return $service;
}

public function updateService(int $id, array $data): bool
{
    $result = (bool) \App\MedicalService::where('id', $id)->update([
        'name'           => $data['name'],
        'price'          => $data['price'],
        'unit'           => $data['unit'] ?? null,
        'description'    => $data['description'] ?? null,
        'category_id'    => $data['category_id'] ?? null,
        'is_active'      => $data['is_active'] ?? true,
        'is_discountable' => $data['is_discountable'] ?? true,
        'is_favorite'    => $data['is_favorite'] ?? false,
        'sort_order'     => $data['sort_order'] ?? 0,
    ]);
    Cache::forget(self::CACHE_KEY_NAMES);
    return $result;
}
```

- [ ] **Step 3: 新增 batchUpdatePrice()**

```php
/**
 * 批量改价：按 category_id（为空表示全部）更新价格，支持百分比或固定金额调整。
 * @param array{mode: 'percent'|'fixed', value: float, category_id?: int|null} $data
 */
public function batchUpdatePrice(array $data): int
{
    $query = \App\MedicalService::whereNull('deleted_at');
    if (!empty($data['category_id'])) {
        $query->where('category_id', $data['category_id']);
    }
    $services = $query->get(['id', 'price']);
    $count = 0;
    foreach ($services as $svc) {
        $newPrice = $data['mode'] === 'percent'
            ? bcmul($svc->price, bcdiv((string)(100 + $data['value']), '100', 4), 2)
            : bcadd($svc->price, (string) $data['value'], 2);
        if (bccomp($newPrice, '0', 2) < 0) {
            $newPrice = '0.00';
        }
        \App\MedicalService::where('id', $svc->id)->update(['price' => $newPrice]);
        $count++;
    }
    Cache::forget(self::CACHE_KEY_NAMES);
    Cache::forget('billing_service_category_tree');
    return $count;
}
```

- [ ] **Step 4: 更新 MedicalServiceController**

在 `store()` 和 `update()` 的 Validator rules 增加新字段：

```php
Validator::make($request->all(), [
    'name'           => 'required|string|max:255',
    'price'          => 'required|numeric|min:0',
    'unit'           => 'nullable|string|max:20',
    'description'    => 'nullable|string|max:500',
    'category_id'    => 'nullable|integer|exists:service_categories,id',
    'is_active'      => 'boolean',
    'is_discountable' => 'boolean',
    'is_favorite'    => 'boolean',
    'sort_order'     => 'integer|min:0',
])->validate();
```

新增 `batchUpdatePrice()` 方法：

```php
public function batchUpdatePrice(Request $request): JsonResponse
{
    $v = Validator::make($request->all(), [
        'mode'        => 'required|in:percent,fixed',
        'value'       => 'required|numeric',
        'category_id' => 'nullable|integer|exists:service_categories,id',
    ]);
    if ($v->fails()) {
        return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
    }
    $count = $this->medicalServiceService->batchUpdatePrice($request->only(['mode', 'value', 'category_id']));
    return response()->json(['status' => 1, 'message' => "已更新 {$count} 条记录"]);
}
```

- [ ] **Step 5: 在 routes/web.php 加路由**

```php
Route::post('clinic-services/batch-update-price', 'MedicalServiceController@batchUpdatePrice')
    ->name('clinic-services.batch-update-price');
```

> 注意：这条路由必须在 `Route::resource('clinic-services', ...)` 之前注册。

- [ ] **Step 6: 验证**

```bash
php artisan route:list | grep clinic-services
```

- [ ] **Step 7: 提交**

```bash
git add App/Services/MedicalServiceService.php \
        App/Http/Controllers/MedicalServiceController.php \
        routes/web.php
git commit -m "feat(billing): upgrade MedicalService CRUD with category_id and batchUpdatePrice"
```

---

## Task 9: 权限 Seeder + Menu Seeder（收费项目部分）

**Files:**
- Modify: `database/seeders/PermissionsTableSeeder.php`
- Modify: `database/seeders/MenuItemsSeeder.php`

- [ ] **Step 1: 在 PermissionsTableSeeder 加 3 条新权限**

在 `// 医疗管理` 分组末尾追加：

```php
['name' => '管理收费大类',  'slug' => 'manage-service-categories', 'module' => '医疗管理', 'description' => '管理收费项目大类分类'],
['name' => '管理收费套餐',  'slug' => 'manage-service-packages',   'module' => '医疗管理', 'description' => '管理收费套餐与明细'],
['name' => '导入收费项目',  'slug' => 'import-medical-services',   'module' => '医疗管理', 'description' => '批量 Excel 导入收费项目'],
```

- [ ] **Step 2: 在 RolesAndPermissionsSeeder（或对应 Seeder）为 super-admin 和 admin 角色关联新权限**

查找当前角色权限分配 Seeder，在 super-admin 和 admin 的权限列表中追加 3 个新 slug。

如果权限关联在 MenuItemsSeeder 外部的另一个 Seeder 中，在那里追加；否则用 tinker 直接关联（Task 5/7 中已做）。

- [ ] **Step 3: 验证 Seeder 可重新跑**

```bash
php artisan db:seed --class=PermissionsTableSeeder
```

期望：无报错，新权限存在。

- [ ] **Step 4: 提交**

```bash
git add database/seeders/PermissionsTableSeeder.php
git commit -m "feat(billing): add manage-service-categories/packages permissions to seeder"
```

---

## Task 10: i18n 翻译键

**Files:**
- Create: `resources/lang/zh-CN/clinic_services.php`（追加新键，保留原键）
- Create: `resources/lang/en/clinic_services.php`（同步）

- [ ] **Step 1: 查看现有 zh-CN/clinical_services.php（注意拼写差异）**

```bash
cat resources/lang/zh-CN/clinical_services.php
```

- [ ] **Step 2: 在对应文件中追加（或创建）以下键**

```php
// zh-CN/clinic_services.php（或 clinical_services.php，与现有文件名保持一致）
return [
    // 原有键保留 ...

    // 新增：大类管理
    'service_categories'             => '收费大类',
    'category_name'                  => '大类名称',
    'category_sort_order'            => '排序',
    'category_is_active'             => '启用',
    'category_created_successfully'  => '大类创建成功',
    'category_updated_successfully'  => '大类更新成功',
    'category_deleted_successfully'  => '大类删除成功',
    'category_name_duplicate'        => '大类名称已存在',

    // 新增：套餐管理
    'service_packages'               => '收费套餐',
    'package_name'                   => '套餐名称',
    'package_total_price'            => '套餐总价',
    'package_description'            => '套餐说明',
    'package_items'                  => '套餐明细',
    'package_item_qty'               => '数量',
    'package_item_price'             => '套餐内单价',
    'package_created_successfully'   => '套餐创建成功',
    'package_updated_successfully'   => '套餐更新成功',
    'package_deleted_successfully'   => '套餐删除成功',

    // 新增：批量改价
    'batch_update_price'             => '批量改价',
    'batch_mode_percent'             => '按百分比调整',
    'batch_mode_fixed'               => '按固定金额调整',
    'batch_value'                    => '调整值',
    'batch_scope_all'                => '全部项目',
    'batch_scope_category'           => '仅当前大类',

    // 新增：项目新字段标签
    'is_discountable'                => '允许打折',
    'is_favorite'                    => '常用项目',
    'unit'                           => '单位',
];
```

- [ ] **Step 3: 提交**

```bash
git add resources/lang/zh-CN/clinic_services.php resources/lang/en/clinic_services.php
git commit -m "feat(i18n): add clinic_services translation keys for categories and packages"
```

---

## Task 11: 视图 — 主页面 + Tab 布局改写

**Files:**
- Modify: `resources/views/clinical_services/index.blade.php`（全面改写）
- Create: `resources/views/clinical_services/_tab_services.blade.php`
- Create: `resources/views/clinical_services/_tab_packages.blade.php`
- Create: `resources/views/clinical_services/_modal_service.blade.php`
- Create: `resources/views/clinical_services/_modal_package.blade.php`
- Create: `resources/views/clinical_services/_modal_import.blade.php`

> 视图文件为纯 HTML 结构，以下给出各文件的关键骨架，具体 CSS class 参考现有页面（如 `resources/views/invoices/index.blade.php`）。

- [ ] **Step 1: 改写 index.blade.php**

```blade
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/clinic_services.css') }}">
@endsection

@section('content')
<div class="page-content">
    <!-- Tab 切换按钮 -->
    <ul class="nav nav-tabs" id="clinicServicesTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tab-services" role="tab">
                {{ __('clinic_services.service_items') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-packages" role="tab">
                {{ __('clinic_services.service_packages') }}
            </a>
        </li>
    </ul>

    <div class="tab-content" id="clinicServicesTabContent">
        <div class="tab-pane fade show active" id="tab-services" role="tabpanel">
            @include('clinical_services._tab_services')
        </div>
        <div class="tab-pane fade" id="tab-packages" role="tabpanel">
            @include('clinical_services._tab_packages')
        </div>
    </div>
</div>

@include('clinical_services._modal_service')
@include('clinical_services._modal_package')
@include('clinical_services._modal_import')
@include('clinical_services._modal_batch_price')
@endsection

@section('js')
<script>
LanguageManager.loadFromPHP(@json(__('clinic_services')), 'clinic_services');
</script>
<script src="{{ asset('include_js/clinic_services.js') }}?v={{ filemtime(public_path('include_js/clinic_services.js')) }}"></script>
@endsection
```

- [ ] **Step 2: 创建 _tab_services.blade.php**

左侧大类树（宽约 220px）+ 右侧 DataTable 布局：

```blade
<div class="row mt-3">
    {{-- 左：大类树 --}}
    <div class="col-md-3" id="category-tree-panel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('clinic_services.service_categories') }}</span>
                @can('manage-service-categories')
                <button class="btn btn-xs btn-primary" id="btn-add-category">+</button>
                @endcan
            </div>
            <ul class="list-group list-group-flush" id="category-list">
                <li class="list-group-item active" data-id="0">
                    {{ __('common.all') }}
                </li>
                {{-- JS 动态渲染 --}}
            </ul>
        </div>
    </div>

    {{-- 右：项目 DataTable --}}
    <div class="col-md-9">
        <div class="d-flex justify-content-between mb-2">
            @can('manage-medical-services')
            <div>
                <button class="btn btn-success" id="btn-add-service">
                    {{ __('common.add') }}
                </button>
                <button class="btn btn-warning ml-1" id="btn-batch-price">
                    {{ __('clinic_services.batch_update_price') }}
                </button>
                <button class="btn btn-info ml-1" id="btn-import">
                    {{ __('common.import') }}
                </button>
                <a class="btn btn-secondary ml-1" href="{{ route('clinic-services.export') }}">
                    {{ __('common.export') }}
                </a>
            </div>
            @endcan
        </div>
        <table id="services-datatable" class="table table-bordered table-hover w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('clinic_services.name') }}</th>
                    <th>{{ __('clinic_services.unit') }}</th>
                    <th>{{ __('clinic_services.price') }}</th>
                    <th>{{ __('clinic_services.is_discountable') }}</th>
                    <th>{{ __('clinic_services.is_favorite') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('common.action') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
```

- [ ] **Step 3: 创建 _tab_packages.blade.php**（仿 DataTable 列表 + 新增按钮）

```blade
<div class="mt-3">
    @can('manage-service-packages')
    <button class="btn btn-success mb-2" id="btn-add-package">
        {{ __('common.add') }}
    </button>
    @endcan
    <table id="packages-datatable" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('clinic_services.package_name') }}</th>
                <th>{{ __('clinic_services.package_total_price') }}</th>
                <th>{{ __('common.status') }}</th>
                <th>{{ __('common.action') }}</th>
            </tr>
        </thead>
    </table>
</div>
```

- [ ] **Step 4: 创建 _modal_service.blade.php**

包含字段：name, price, unit, description, category_id（Select2 下拉绑定大类列表）, is_discountable, is_favorite, is_active, sort_order。

隐藏字段 `#service-id` 用于区分新增/编辑。

```blade
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="service-modal-title">{{ __('common.add') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="service-id">
                <div class="form-group">
                    <label>{{ __('clinic_services.name') }} *</label>
                    <input type="text" class="form-control" id="service-name">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>{{ __('clinic_services.price') }} *</label>
                        <input type="number" step="0.01" class="form-control" id="service-price">
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ __('clinic_services.unit') }}</label>
                        <input type="text" class="form-control" id="service-unit" placeholder="次">
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('clinic_services.service_categories') }}</label>
                    <select class="form-control select2" id="service-category-id"></select>
                </div>
                <div class="form-group">
                    <label>{{ __('clinic_services.description') }}</label>
                    <textarea class="form-control" id="service-description" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="service-is-discountable" checked>
                            <label class="custom-control-label" for="service-is-discountable">
                                {{ __('clinic_services.is_discountable') }}
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="service-is-favorite">
                            <label class="custom-control-label" for="service-is-favorite">
                                {{ __('clinic_services.is_favorite') }}
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="service-is-active" checked>
                            <label class="custom-control-label" for="service-is-active">
                                {{ __('common.active') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-service">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: 创建 _modal_package.blade.php**

包含：name, description, total_price, is_active, 以及动态明细行（service_id Select2 + qty + price + 删除按钮 + 添加行按钮）。

- [ ] **Step 6: 创建 _modal_batch_price.blade.php**

```blade
<div class="modal fade" id="batchPriceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('clinic_services.batch_update_price') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    {{ __('clinic_services.batch_scope_' . 'all') }} —
                    当前选中大类下的全部项目（若未选大类则为所有项目）
                </p>
                <div class="form-group">
                    <label>调整方式</label>
                    <div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" class="custom-control-input" name="batch-mode" id="mode-percent" value="percent" checked>
                            <label class="custom-control-label" for="mode-percent">{{ __('clinic_services.batch_mode_percent') }}</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" class="custom-control-input" name="batch-mode" id="mode-fixed" value="fixed">
                            <label class="custom-control-label" for="mode-fixed">{{ __('clinic_services.batch_mode_fixed') }}</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('clinic_services.batch_value') }}</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" id="batch-price-value" placeholder="例：10 表示涨价10%或涨价10元；-10 表示降价">
                        <div class="input-group-append">
                            <span class="input-group-text batch-unit-label">%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-warning" id="btn-confirm-batch-price">确认改价</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 7: 创建 _modal_import.blade.php**

包含：file input（.xlsx/.xls），模板下载链接，字段说明（name, price, unit, category）。

- [ ] **Step 8: 创建 _modal_import.blade.php** (实际 Step，编号调整后保持一致)

```blade
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('common.import') }}收费项目</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>
                    <a href="{{ route('clinic-services.export') }}" class="btn btn-sm btn-outline-secondary">
                        下载导入模板
                    </a>
                </p>
                <p class="text-muted small">
                    模板列说明：name（必填）、price（必填）、unit、category（大类名称，不存在则自动创建）
                </p>
                <form id="import-form" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>选择文件（.xlsx / .xls）</label>
                        <input type="file" class="form-control-file" id="import-file" name="file" accept=".xlsx,.xls">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-import">开始导入</button>
            </div>
        </div>
    </div>
</div>
```

在 index.blade.php 的 `@include` 列表中追加：
```blade
@include('clinical_services._modal_batch_price')
```

- [ ] **Step 9: 浏览器验证**

```bash
php artisan serve
```

访问 `/clinic-services`，确认：
- Tab 切换正常
- 左侧大类树渲染（调用 `/admin/service-categories`）
- DataTable 加载（调用 `/clinic-services` AJAX）
- 新增/编辑弹框正常

- [ ] **Step 8: 提交**

```bash
git add resources/views/clinical_services/
git commit -m "feat(billing): rewrite clinic-services views with category tree and package tab"
```

---

## Task 12: clinic_services.js + clinic_services.css

**Files:**
- Create: `public/include_js/clinic_services.js`
- Create: `public/css/clinic_services.css`

- [ ] **Step 1: 创建 clinic_services.js**

文件结构：

```javascript
// public/include_js/clinic_services.js
'use strict';

/* ── 全局变量 ──────────────────────────────────────── */
let servicesTable = null;
let packagesTable = null;
let currentCategoryId = 0; // 0 = 全部

/* ── 初始化 ────────────────────────────────────────── */
$(document).ready(function () {
    initCategoryTree();
    initServicesTable();
    initPackagesTable();
    bindServiceModal();
    bindPackageModal();
    bindBatchPriceModal();
    bindImportModal();
});

/* ── 1. 大类树 ─────────────────────────────────────── */
function initCategoryTree() {
    $.get('/admin/service-categories', function (res) {
        if (!res.status) return;
        const $list = $('#category-list');
        res.data.forEach(function (cat) {
            $list.append(
                `<li class="list-group-item d-flex justify-content-between align-items-center"
                     data-id="${cat.id}">
                     ${cat.name}
                     <span>
                         <i class="fa fa-edit text-primary cursor-pointer" onclick="editCategory(${cat.id}, '${cat.name}', ${cat.sort_order}, ${cat.is_active})"></i>
                         <i class="fa fa-trash text-danger cursor-pointer ml-1" onclick="deleteCategory(${cat.id})"></i>
                     </span>
                 </li>`
            );
        });
    });

    $(document).on('click', '#category-list .list-group-item', function () {
        $('#category-list .list-group-item').removeClass('active');
        $(this).addClass('active');
        currentCategoryId = $(this).data('id');
        servicesTable.ajax.reload();
    });
}

/* ── 2. 项目 DataTable ─────────────────────────────── */
function initServicesTable() {
    servicesTable = $('#services-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/clinic-services',
            data: function (d) {
                d.category_id = currentCategoryId || null;
            },
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'name' },
            { data: 'unit', defaultContent: '次' },
            { data: 'price', render: function (v) { return parseFloat(v).toLocaleString(); } },
            { data: 'is_discountable', render: function (v) { return v ? '✓' : '✗'; } },
            { data: 'is_favorite', render: function (v) { return v ? '★' : '☆'; } },
            { data: 'is_active', render: function (v) { return v ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-secondary">停用</span>'; } },
            { data: 'action', orderable: false },
        ],
        language: { url: '/vendor/datatables/zh-CN.json' },
    });
}

/* ── 3. 套餐 DataTable ─────────────────────────────── */
function initPackagesTable() {
    packagesTable = $('#packages-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/service-packages',
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'name' },
            { data: 'total_price', render: function (v) { return parseFloat(v).toLocaleString(); } },
            { data: 'is_active', render: function (v) { return v ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-secondary">停用</span>'; } },
            { data: 'action', orderable: false },
        ],
        language: { url: '/vendor/datatables/zh-CN.json' },
    });
}

/* ── 4. 项目弹框 ──────────────────────────────────── */
function bindServiceModal() {
    $('#btn-add-service').click(function () {
        resetServiceModal();
        $('#serviceModal').modal('show');
    });

    $('#btn-save-service').click(function () {
        const id = $('#service-id').val();
        const url = id ? `/clinic-services/${id}` : '/clinic-services';
        const method = id ? 'PUT' : 'POST';
        $.ajax({
            url, method,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name:           $('#service-name').val(),
                price:          $('#service-price').val(),
                unit:           $('#service-unit').val(),
                description:    $('#service-description').val(),
                category_id:    $('#service-category-id').val() || null,
                is_active:      $('#service-is-active').is(':checked') ? 1 : 0,
                is_discountable: $('#service-is-discountable').is(':checked') ? 1 : 0,
                is_favorite:    $('#service-is-favorite').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                if (res.status) {
                    $('#serviceModal').modal('hide');
                    servicesTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

function resetServiceModal() {
    $('#service-id').val('');
    $('#service-name, #service-price, #service-unit, #service-description').val('');
    $('#service-category-id').val(null).trigger('change');
    $('#service-is-discountable, #service-is-active').prop('checked', true);
    $('#service-is-favorite').prop('checked', false);
    $('#service-modal-title').text(LanguageManager.trans('common.add'));
}

function editRecord(id) {
    $.get(`/clinic-services/${id}/edit`, function (data) {
        $('#service-id').val(data.id);
        $('#service-name').val(data.name);
        $('#service-price').val(data.price);
        $('#service-unit').val(data.unit);
        $('#service-description').val(data.description);
        // Select2: 先设值再触发 change
        const opt = new Option(data.category_name || '', data.category_id, true, true);
        $('#service-category-id').append(opt).trigger('change');
        $('#service-is-discountable').prop('checked', !!data.is_discountable);
        $('#service-is-favorite').prop('checked', !!data.is_favorite);
        $('#service-is-active').prop('checked', !!data.is_active);
        $('#service-modal-title').text(LanguageManager.trans('common.edit'));
        $('#serviceModal').modal('show');
    });
}

function deleteRecord(id) {
    Swal.fire({
        title: LanguageManager.trans('common.confirm_delete'),
        icon: 'warning', showCancelButton: true,
        confirmButtonText: LanguageManager.trans('common.delete'),
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: `/clinic-services/${id}`,
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.status) {
                        servicesTable.ajax.reload();
                        toastr.success(res.message);
                    } else {
                        toastr.error(res.message);
                    }
                },
            });
        }
    });
}

/* ── 5. 批量改价弹框 ──────────────────────────────── */
function bindBatchPriceModal() {
    // 弹框 HTML 内嵌在 _tab_services.blade.php 或单独 _modal_batch_price.blade.php
    $('#btn-batch-price').click(function () {
        $('#batchPriceModal').modal('show');
    });
    $('#btn-confirm-batch-price').click(function () {
        $.ajax({
            url: '/clinic-services/batch-update-price',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                mode:        $('input[name="batch-mode"]:checked').val(),
                value:       $('#batch-price-value').val(),
                category_id: currentCategoryId || null,
            },
            success: function (res) {
                if (res.status) {
                    $('#batchPriceModal').modal('hide');
                    servicesTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

/* ── 6. 导入弹框 ──────────────────────────────────── */
function bindImportModal() {
    $('#btn-import').click(function () { $('#importModal').modal('show'); });
    // file upload via FormData — 实现略
}

/* ── 7. 套餐弹框 ──────────────────────────────────── */
function bindPackageModal() {
    $('#btn-add-package').click(function () {
        resetPackageModal();
        $('#packageModal').modal('show');
    });
    // editPackage / deletePackage / save 略（同 service 模式）
}

function resetPackageModal() {
    $('#package-id, #package-name, #package-price, #package-description').val('');
    $('#package-items-body').empty();
}

/* ── 8. 大类管理函数 ──────────────────────────────── */
function editCategory(id, name, sortOrder, isActive) {
    // 弹框编辑大类
    Swal.fire({
        title: LanguageManager.trans('common.edit'),
        input: 'text', inputValue: name,
        showCancelButton: true,
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/service-categories/${id}`,
                method: 'PUT',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), name: result.value },
                success: function (res) {
                    if (res.status) { location.reload(); }
                    else { toastr.error(res.message); }
                },
            });
        }
    });
}

function deleteCategory(id) {
    Swal.fire({
        title: LanguageManager.trans('common.confirm_delete'),
        icon: 'warning', showCancelButton: true,
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/service-categories/${id}`,
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.status) { location.reload(); }
                    else { toastr.error(res.message); }
                },
            });
        }
    });
}
```

- [ ] **Step 2: 创建 clinic_services.css**

```css
/* public/css/clinic_services.css */

#category-tree-panel .list-group-item {
    cursor: pointer;
    padding: 8px 12px;
    font-size: 13px;
}

#category-tree-panel .list-group-item.active {
    background-color: #3699ff;
    border-color: #3699ff;
    color: #fff;
}

#category-tree-panel .list-group-item.active i {
    color: #fff !important;
}

.cursor-pointer {
    cursor: pointer;
}

#packages-datatable .badge {
    font-size: 12px;
}
```

- [ ] **Step 3: 验证页面功能（浏览器手工测试清单）**

- [ ] 左侧大类树渲染正常
- [ ] 点击大类，右侧表格过滤
- [ ] 新增项目弹框：category_id 下拉有内容，保存成功
- [ ] 编辑项目弹框：字段正确回填，保存成功
- [ ] 批量改价：百分比和固定金额两种模式均正常
- [ ] Tab 切换到套餐，DataTable 加载

- [ ] **Step 4: 提交**

```bash
git add public/include_js/clinic_services.js \
        public/css/clinic_services.css
git commit -m "feat(billing): add clinic_services.js and clinic_services.css"
```

---

## Task 12b: Excel 导入 / 导出

**Files:**
- Create: `App/Imports/MedicalServicesImport.php`
- Modify: `App/Services/MedicalServiceService.php` (add importFromExcel / getExportData)
- Modify: `App/Http/Controllers/MedicalServiceController.php` (add import / export methods)
- Modify: `routes/web.php` (add import/export routes, before resource)

- [ ] **Step 1: 创建 MedicalServicesImport**

```php
<?php
// App/Imports/MedicalServicesImport.php
namespace App\Imports;

use App\ServiceCategory;
use App\MedicalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MedicalServicesImport implements ToCollection, WithHeadingRow, WithValidation
{
    public int $importedCount = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim($row['name'] ?? '');
            if (!$name) continue;

            $categoryId = null;
            if (!empty($row['category'])) {
                $cat = ServiceCategory::firstOrCreate(
                    ['name' => trim($row['category'])],
                    ['sort_order' => 0, 'is_active' => true, '_who_added' => Auth::id()]
                );
                $categoryId = $cat->id;
            }

            MedicalService::updateOrCreate(
                ['name' => $name],
                [
                    'price'       => $row['price'] ?? 0,
                    'unit'        => $row['unit'] ?? null,
                    'category_id' => $categoryId,
                    '_who_added'  => Auth::id(),
                ]
            );
            $this->importedCount++;
        }
        \Illuminate\Support\Facades\Cache::forget('billing_service_category_tree');
    }

    public function rules(): array
    {
        return ['name' => 'required'];
    }
}
```

- [ ] **Step 2: 在 MedicalServiceController 加 import / export 方法**

```php
public function import(Request $request): JsonResponse
{
    $v = Validator::make($request->all(), [
        'file' => 'required|file|mimes:xlsx,xls|max:2048',
    ]);
    if ($v->fails()) {
        return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
    }

    $importer = new \App\Imports\MedicalServicesImport();
    \Maatwebsite\Excel\Facades\Excel::import($importer, $request->file('file'));

    return response()->json([
        'status'  => 1,
        'message' => "成功导入 {$importer->importedCount} 条记录",
    ]);
}

public function export()
{
    $headers = ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $filename = '收费项目_' . now()->format('Ymd') . '.xlsx';

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\MedicalServicesExport(),
        $filename
    );
}
```

- [ ] **Step 3: 创建 App/Exports/MedicalServicesExport.php**

```php
<?php
// App/Exports/MedicalServicesExport.php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MedicalServicesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DB::table('medical_services')
            ->leftJoin('service_categories', 'service_categories.id', '=', 'medical_services.category_id')
            ->whereNull('medical_services.deleted_at')
            ->select([
                'medical_services.name',
                'medical_services.price',
                'medical_services.unit',
                'service_categories.name as category',
            ])
            ->orderBy('medical_services.id')
            ->get();
    }

    public function headings(): array
    {
        return ['name', 'price', 'unit', 'category'];
    }
}
```

- [ ] **Step 4: 在 routes/web.php 加路由（在 resource 之前）**

```php
Route::post('clinic-services/import', 'MedicalServiceController@import')
    ->name('clinic-services.import')
    ->middleware('can:import-medical-services');
Route::get('clinic-services/export', 'MedicalServiceController@export')
    ->name('clinic-services.export')
    ->middleware('can:manage-medical-services');
```

- [ ] **Step 5: 在 clinic_services.js 中 bindImportModal 补完 FormData 提交**

```javascript
function bindImportModal() {
    $('#btn-import').click(function () { $('#importModal').modal('show'); });

    $('#btn-confirm-import').click(function () {
        const formData = new FormData($('#import-form')[0]);
        $.ajax({
            url:         '/clinic-services/import',
            method:      'POST',
            data:        formData,
            contentType: false,
            processData: false,
            success: function (res) {
                if (res.status) {
                    $('#importModal').modal('hide');
                    servicesTable.ajax.reload();
                    initCategoryTree(); // 刷新大类树（新类目可能被导入）
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}
```

- [ ] **Step 6: 验证导入/导出**

```bash
php artisan route:list | grep clinic-services
```

确认 import（POST）和 export（GET）路由存在，且排在 resource 路由之前。

- [ ] **Step 7: 提交**

```bash
git add App/Imports/MedicalServicesImport.php \
        App/Exports/MedicalServicesExport.php \
        App/Http/Controllers/MedicalServiceController.php \
        public/include_js/clinic_services.js \
        routes/web.php
git commit -m "feat(billing): add Excel import/export for medical services"
```

---

## Task 13: 联调验证 + billing/service-categories 路由兼容性

**Files:**
- Read: `routes/web.php`（检查冲突）
- Read: `App/Http/Controllers/InvoiceController.php`（getServiceCategories 方法）

- [ ] **Step 1: 确认两条路由不冲突**

```bash
php artisan route:list | grep service-categories
```

期望看到：
- `GET billing/service-categories/{patientId}` → `InvoiceController@getServiceCategories`
- `GET admin/service-categories` → `ServiceCategoryController@index`

两者路径前缀不同（`billing/` vs `admin/`），不会冲突。

- [ ] **Step 2: 在划价页面测试 getServiceCategoryTree()**

进入账单划价页面，确认左侧服务分类树以 `service_categories.name` 为分组，数据正确。

- [ ] **Step 3: 最终提交**

```bash
php artisan test tests/Feature/ServiceCategoryTest.php tests/Feature/ServicePackageTest.php
git add .
git commit -m "feat(billing): billing services upgrade complete - service_categories + packages + UI"
```

---

## 实现顺序摘要

```
Task 1  → Task 2  → Task 3  (迁移 3 个文件，顺序执行)
Task 4                        (模型)
Task 5  → Task 6  → Task 7   (大类 / InvoiceService / 套餐，可并行后合并)
Task 8                        (升级现有 MedicalService CRUD)
Task 9                        (Seeder 统一 cleanup)
Task 10                       (i18n)
Task 11 → Task 12 → Task 13  (视图 → JS/CSS → 联调)
```
