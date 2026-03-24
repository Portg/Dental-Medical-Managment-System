# 消毒登记模块 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 全新实现消毒登记功能，包括器械包台账 → 灭菌批次 → 使用记录完整追溯链，新增「诊所事务」一级导航菜单。

**Architecture:** 4 张新表（sterilization_kits / sterilization_kit_instruments / sterilization_records / sterilization_usages），SterilizationService 负责批次号生成（DB 行锁）、冗余字段自动填充、过期实时判断，视图采用双 Tab（灭菌记录 + 器械包），JS/CSS 独立文件。

**Tech Stack:** PHP 8.2, Laravel 11, MySQL, Yajra DataTables, Maatwebsite/Excel 3.1, Bootstrap 4, jQuery, SweetAlert2

**Spec 文档:** `docs/superpowers/specs/2026-03-23-billing-sterilization-design.md` §三（3.2）~§九（消毒部分）

**前提条件:** `billing-services-upgrade` 计划的 Task 9（PermissionsTableSeeder）已执行，菜单 Seeder 结构已熟悉。本计划可独立执行，但如果两个计划并行，需确保 PermissionsTableSeeder 的 seeder run 只触发一次（或两计划的 Seeder 合并后一次 run）。

---

## File Map

### 新建文件

| 文件 | 职责 |
|------|------|
| `database/migrations/2026_03_23_000004_create_sterilization_tables.php` | 4 张消毒表 |
| `App/SterilizationKit.php` | 器械包模型 |
| `App/SterilizationKitInstrument.php` | 器械包明细模型 |
| `App/SterilizationRecord.php` | 灭菌批次模型（含 status 常量） |
| `App/SterilizationUsage.php` | 使用记录模型 |
| `App/Services/SterilizationKitService.php` | 器械包台账 CRUD |
| `App/Services/SterilizationService.php` | 批次号生成、使用登记、过期标记 |
| `App/Http/Controllers/SterilizationKitController.php` | 器械包 CRUD API |
| `App/Http/Controllers/SterilizationController.php` | 灭菌记录 CRUD + 使用登记 + 导出 |
| `resources/views/sterilization/index.blade.php` | 主页面 |
| `resources/views/sterilization/_tab_records.blade.php` | 灭菌记录 Tab |
| `resources/views/sterilization/_tab_kits.blade.php` | 器械包管理 Tab |
| `resources/views/sterilization/_modal_record.blade.php` | 新增/编辑灭菌记录弹框 |
| `resources/views/sterilization/_modal_kit.blade.php` | 新增/编辑器械包弹框（含明细行） |
| `resources/views/sterilization/_modal_use.blade.php` | 登记使用弹框 |
| `public/include_js/sterilization.js` | 页面 JS |
| `public/css/sterilization.css` | 页面 CSS |
| `resources/lang/zh-CN/sterilization.php` | 消毒模块翻译 |
| `resources/lang/en/sterilization.php` | 英文翻译 |
| `tests/Feature/SterilizationBatchNoTest.php` | 批次号生成逻辑单元测试 |
| `tests/Feature/SterilizationUsageTest.php` | 使用登记 + 软删除回滚测试 |

### 修改文件

| 文件 | 修改内容 |
|------|---------|
| `database/seeders/PermissionsTableSeeder.php` | 新增 view-sterilization / manage-sterilization 权限 |
| `database/seeders/MenuItemsSeeder.php` | 新增 seedClinicAffairs() + 「诊所事务」一级菜单 |
| `routes/web.php` | 新增 sterilization / sterilization-kits 路由 |
| `resources/lang/zh-CN/menu.php` | 新增 clinic_affairs / sterilization_management 键 |
| `resources/lang/en/menu.php` | 同步英文键 |

---

## Task 1: 数据库迁移 — 4 张消毒表

**Files:**
- Create: `database/migrations/2026_03_23_000004_create_sterilization_tables.php`

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
        // 1. 器械包台账
        Schema::create('sterilization_kits', function (Blueprint $table) {
            $table->id();
            $table->string('kit_no', 50)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('_who_added')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. 器械包明细（无 deleted_at，物理删除）
        Schema::create('sterilization_kit_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained('sterilization_kits')->cascadeOnDelete();
            $table->string('instrument_name', 100);
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
        });

        // 3. 灭菌批次
        Schema::create('sterilization_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained('sterilization_kits');
            $table->string('batch_no', 50)->unique();
            $table->enum('method', ['autoclave', 'chemical', 'dry_heat']);
            $table->decimal('temperature', 5, 1)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->foreignId('operator_id')->constrained('users');
            $table->dateTime('sterilized_at');
            $table->dateTime('expires_at');
            $table->enum('status', ['valid', 'used', 'voided'])->default('valid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expires_at']); // 过期查询优化
        });

        // 4. 使用记录（追溯）
        Schema::create('sterilization_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('sterilization_records');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('used_by')->constrained('users');
            $table->dateTime('used_at');
            $table->text('notes')->nullable();
            // 冗余快照字段
            $table->string('patient_name', 100)->nullable();
            $table->string('doctor_name', 100)->nullable();
            $table->string('kit_name', 100)->nullable();
            $table->string('batch_no', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sterilization_usages');
        Schema::dropIfExists('sterilization_records');
        Schema::dropIfExists('sterilization_kit_instruments');
        Schema::dropIfExists('sterilization_kits');
    }
};
```

- [ ] **Step 2: 运行迁移**

```bash
php artisan migrate --path=database/migrations/2026_03_23_000004_create_sterilization_tables.php
```

期望：4 表创建成功。

- [ ] **Step 3: 提交**

```bash
git add database/migrations/2026_03_23_000004_create_sterilization_tables.php
git commit -m "feat(db): create sterilization_kits, records, usages tables"
```

---

## Task 2: 模型 — 4 个消毒相关模型

**Files:**
- Create: `App/SterilizationKit.php`
- Create: `App/SterilizationKitInstrument.php`
- Create: `App/SterilizationRecord.php`
- Create: `App/SterilizationUsage.php`

- [ ] **Step 1: SterilizationKit 模型**

```php
<?php
// App/SterilizationKit.php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SterilizationKit extends Model
{
    use SoftDeletes;

    protected $fillable = ['kit_no', 'name', 'is_active', '_who_added'];

    protected $casts = ['is_active' => 'boolean'];

    public function instruments()
    {
        return $this->hasMany(SterilizationKitInstrument::class, 'kit_id')->orderBy('sort_order');
    }

    public function records()
    {
        return $this->hasMany(SterilizationRecord::class, 'kit_id');
    }
}
```

- [ ] **Step 2: SterilizationKitInstrument 模型**

```php
<?php
// App/SterilizationKitInstrument.php
namespace App;

use Illuminate\Database\Eloquent\Model;

class SterilizationKitInstrument extends Model
{
    public $timestamps = false;
    protected $fillable = ['kit_id', 'instrument_name', 'quantity', 'sort_order'];

    public function kit()
    {
        return $this->belongsTo(SterilizationKit::class, 'kit_id');
    }
}
```

- [ ] **Step 3: SterilizationRecord 模型**

```php
<?php
// App/SterilizationRecord.php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SterilizationRecord extends Model
{
    use SoftDeletes;

    const STATUS_VALID  = 'valid';
    const STATUS_USED   = 'used';
    const STATUS_VOIDED = 'voided';

    const METHOD_AUTOCLAVE = 'autoclave';
    const METHOD_CHEMICAL  = 'chemical';
    const METHOD_DRY_HEAT  = 'dry_heat';

    // 有效期天数（按灭菌方式）
    const EXPIRY_DAYS = [
        self::METHOD_AUTOCLAVE => 90,
        self::METHOD_DRY_HEAT  => 90,
        self::METHOD_CHEMICAL  => 30,
    ];

    protected $fillable = [
        'kit_id', 'batch_no', 'method', 'temperature', 'duration_minutes',
        'operator_id', 'sterilized_at', 'expires_at', 'status', 'notes',
    ];

    protected $casts = [
        'sterilized_at' => 'datetime',
        'expires_at'    => 'datetime',
    ];

    /**
     * 实时判断是否已过期（不依赖 status 字段）
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->status === self::STATUS_VALID;
    }

    public function kit()
    {
        return $this->belongsTo(SterilizationKit::class, 'kit_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function usage()
    {
        return $this->hasOne(SterilizationUsage::class, 'record_id')->whereNull('deleted_at');
    }
}
```

- [ ] **Step 4: SterilizationUsage 模型**

```php
<?php
// App/SterilizationUsage.php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SterilizationUsage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'record_id', 'appointment_id', 'patient_id', 'used_by',
        'used_at', 'notes',
        'patient_name', 'doctor_name', 'kit_name', 'batch_no',
    ];

    protected $casts = ['used_at' => 'datetime'];

    public function record()
    {
        return $this->belongsTo(SterilizationRecord::class, 'record_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
```

- [ ] **Step 5: tinker 验证模型**

```bash
php artisan tinker --execute="
use App\SterilizationKit;
use App\SterilizationRecord;
echo 'Models loaded OK' . PHP_EOL;
echo 'Record status constants: ' . SterilizationRecord::STATUS_VALID . PHP_EOL;
"
```

- [ ] **Step 6: 提交**

```bash
git add App/SterilizationKit.php App/SterilizationKitInstrument.php \
        App/SterilizationRecord.php App/SterilizationUsage.php
git commit -m "feat(model): add 4 sterilization models"
```

---

## Task 3: SterilizationService — 批次号生成 + 使用登记核心逻辑

**Files:**
- Create: `App/Services/SterilizationService.php`
- Create: `tests/Feature/SterilizationBatchNoTest.php`
- Create: `tests/Feature/SterilizationUsageTest.php`

- [ ] **Step 1: 写批次号测试**

```php
<?php
// tests/Feature/SterilizationBatchNoTest.php
namespace Tests\Feature;

use App\Services\SterilizationService;
use App\SterilizationKit;
use App\SterilizationRecord;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SterilizationBatchNoTest extends TestCase
{
    use RefreshDatabase;

    private SterilizationService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(SterilizationService::class);
    }

    private function makeKit(): SterilizationKit
    {
        return SterilizationKit::create([
            'kit_no' => 'KIT-001', 'name' => '洁牙包', 'is_active' => true,
        ]);
    }

    private function makeOperator(): User
    {
        return User::factory()->create();
    }

    public function test_generates_batch_no_with_correct_format(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();
        $record = $this->svc->createRecord([
            'kit_id'            => $kit->id,
            'method'            => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'       => $op->id,
            'sterilized_at'     => now(),
        ]);
        $this->assertMatchesRegularExpression('/^S\d{8}-\d{3}$/', $record->batch_no);
    }

    public function test_batch_no_increments_within_same_day(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();

        $r1 = $this->svc->createRecord(['kit_id' => $kit->id, 'method' => SterilizationRecord::METHOD_AUTOCLAVE, 'operator_id' => $op->id, 'sterilized_at' => now()]);
        $r2 = $this->svc->createRecord(['kit_id' => $kit->id, 'method' => SterilizationRecord::METHOD_AUTOCLAVE, 'operator_id' => $op->id, 'sterilized_at' => now()]);

        $seq1 = (int) substr($r1->batch_no, -3);
        $seq2 = (int) substr($r2->batch_no, -3);
        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_autoclave_expires_in_90_days(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();
        $record = $this->svc->createRecord([
            'kit_id'        => $kit->id,
            'method'        => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'   => $op->id,
            'sterilized_at' => now(),
        ]);
        $diffDays = (int) now()->diffInDays($record->expires_at);
        $this->assertEquals(90, $diffDays);
    }

    public function test_chemical_expires_in_30_days(): void
    {
        $kit = $this->makeKit();
        $op  = $this->makeOperator();
        $record = $this->svc->createRecord([
            'kit_id'        => $kit->id,
            'method'        => SterilizationRecord::METHOD_CHEMICAL,
            'operator_id'   => $op->id,
            'sterilized_at' => now(),
        ]);
        $diffDays = (int) now()->diffInDays($record->expires_at);
        $this->assertEquals(30, $diffDays);
    }
}
```

- [ ] **Step 2: 写使用登记测试**

```php
<?php
// tests/Feature/SterilizationUsageTest.php
namespace Tests\Feature;

use App\Services\SterilizationService;
use App\SterilizationKit;
use App\SterilizationRecord;
use App\SterilizationUsage;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SterilizationUsageTest extends TestCase
{
    use RefreshDatabase;

    private SterilizationService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(SterilizationService::class);
    }

    private function makeRecord(): SterilizationRecord
    {
        $kit = SterilizationKit::create(['kit_no' => 'K1', 'name' => '测试包', 'is_active' => true]);
        $op  = User::factory()->create();
        return $this->svc->createRecord([
            'kit_id'        => $kit->id,
            'method'        => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'   => $op->id,
            'sterilized_at' => now(),
        ]);
    }

    public function test_record_usage_sets_status_to_used(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create();
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $record->refresh();
        $this->assertEquals(SterilizationRecord::STATUS_USED, $record->status);
    }

    public function test_record_usage_fills_snapshot_fields(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create(['name' => '张医生']);
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $usage = SterilizationUsage::where('record_id', $record->id)->first();
        $this->assertEquals($record->batch_no, $usage->batch_no);
        $this->assertNotNull($usage->kit_name);
        $this->assertNotNull($usage->doctor_name);
    }

    public function test_soft_delete_usage_rolls_back_status(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create();
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $usage = SterilizationUsage::where('record_id', $record->id)->first();
        $this->svc->revokeUsage($usage->id);

        $record->refresh();
        $this->assertEquals(SterilizationRecord::STATUS_VALID, $record->status);
        $this->assertSoftDeleted($usage);
    }

    public function test_cannot_use_already_used_record(): void
    {
        $record = $this->makeRecord();
        $user   = User::factory()->create();
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);

        $this->expectException(\RuntimeException::class);
        $this->svc->recordUsage($record->id, ['used_by' => $user->id, 'used_at' => now()]);
    }

    public function test_cannot_use_expired_record(): void
    {
        $kit = SterilizationKit::create(['kit_no' => 'K2', 'name' => '过期包', 'is_active' => true]);
        $op  = User::factory()->create();
        // 直接创建一条已过期的记录（expires_at 设为过去）
        $record = SterilizationRecord::create([
            'kit_id'        => $kit->id,
            'batch_no'      => 'S20200101-001',
            'method'        => SterilizationRecord::METHOD_AUTOCLAVE,
            'operator_id'   => $op->id,
            'sterilized_at' => now()->subDays(100),
            'expires_at'    => now()->subDays(10),
            'status'        => SterilizationRecord::STATUS_VALID,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->svc->recordUsage($record->id, ['used_by' => $op->id, 'used_at' => now()]);
    }
}
```

- [ ] **Step 3: 跑测试，确认失败**

```bash
php artisan test tests/Feature/SterilizationBatchNoTest.php tests/Feature/SterilizationUsageTest.php
```

- [ ] **Step 4: 实现 SterilizationService**

```php
<?php
// App/Services/SterilizationService.php
namespace App\Services;

use App\SterilizationKit;
use App\SterilizationRecord;
use App\SterilizationUsage;
use App\User;
use App\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SterilizationService
{
    /**
     * 创建灭菌记录（含批次号生成 + 有效期计算）
     */
    public function createRecord(array $data): SterilizationRecord
    {
        return DB::transaction(function () use ($data) {
            $batchNo    = $this->generateBatchNo();
            $expiryDays = SterilizationRecord::EXPIRY_DAYS[$data['method']] ?? 90;
            $sterilizedAt = $data['sterilized_at'] instanceof \Carbon\Carbon
                ? $data['sterilized_at']
                : \Carbon\Carbon::parse($data['sterilized_at']);

            return SterilizationRecord::create([
                'kit_id'           => $data['kit_id'],
                'batch_no'         => $batchNo,
                'method'           => $data['method'],
                'temperature'      => $data['temperature'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'operator_id'      => $data['operator_id'] ?? Auth::id(),
                'sterilized_at'    => $sterilizedAt,
                'expires_at'       => $sterilizedAt->copy()->addDays($expiryDays),
                'status'           => SterilizationRecord::STATUS_VALID,
                'notes'            => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * 批次号生成：S{YYYYMMDD}-{NNN}，行锁防并发重复
     */
    private function generateBatchNo(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "S{$date}-";

        // FOR UPDATE 行锁，确保并发时序号唯一
        $last = DB::table('sterilization_records')
            ->where('batch_no', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderBy('batch_no', 'desc')
            ->value('batch_no');

        $seq = $last ? (int) substr($last, -3) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 登记使用：校验状态 → 创建 usage → 更新 record.status
     * @throws \RuntimeException 若记录已使用或已过期
     */
    public function recordUsage(int $recordId, array $data): SterilizationUsage
    {
        return DB::transaction(function () use ($recordId, $data) {
            $record = SterilizationRecord::lockForUpdate()->findOrFail($recordId);

            if ($record->status === SterilizationRecord::STATUS_USED) {
                throw new \RuntimeException('该灭菌批次已被使用，无法重复登记');
            }
            if ($record->isExpired()) {
                throw new \RuntimeException('该灭菌批次已过期，无法登记使用');
            }

            // 查询冗余快照字段
            $kit     = SterilizationKit::find($record->kit_id);
            $doctor  = User::find($data['used_by']);
            $patient = isset($data['patient_id']) ? Patient::find($data['patient_id']) : null;

            $usage = SterilizationUsage::create([
                'record_id'      => $record->id,
                'appointment_id' => $data['appointment_id'] ?? null,
                'patient_id'     => $data['patient_id'] ?? null,
                'used_by'        => $data['used_by'],
                'used_at'        => $data['used_at'],
                'notes'          => $data['notes'] ?? null,
                // 冗余快照
                'patient_name'   => $patient ? $patient->name : null,
                'doctor_name'    => $doctor ? $doctor->name : null,
                'kit_name'       => $kit ? $kit->name : null,
                'batch_no'       => $record->batch_no,
            ]);

            $record->update(['status' => SterilizationRecord::STATUS_USED]);

            return $usage;
        });
    }

    /**
     * 撤销使用（软删除 usage + 回滚 record.status）
     */
    public function revokeUsage(int $usageId): void
    {
        DB::transaction(function () use ($usageId) {
            $usage = SterilizationUsage::findOrFail($usageId);
            $usage->delete(); // 软删除

            SterilizationRecord::where('id', $usage->record_id)
                ->update(['status' => SterilizationRecord::STATUS_VALID]);
        });
    }

    /**
     * 更新灭菌记录（仅允许修改 valid 状态的记录）
     */
    public function updateRecord(int $id, array $data): bool
    {
        $record = SterilizationRecord::findOrFail($id);
        if ($record->status !== SterilizationRecord::STATUS_VALID) {
            throw new \RuntimeException('已使用或已作废的记录不可修改');
        }

        $expiryDays = SterilizationRecord::EXPIRY_DAYS[$data['method'] ?? $record->method] ?? 90;
        $sterilizedAt = isset($data['sterilized_at'])
            ? \Carbon\Carbon::parse($data['sterilized_at'])
            : $record->sterilized_at;

        return (bool) $record->update([
            'kit_id'           => $data['kit_id'] ?? $record->kit_id,
            'method'           => $data['method'] ?? $record->method,
            'temperature'      => $data['temperature'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'operator_id'      => $data['operator_id'] ?? $record->operator_id,
            'sterilized_at'    => $sterilizedAt,
            'expires_at'       => $sterilizedAt->copy()->addDays($expiryDays),
            'notes'            => $data['notes'] ?? null,
        ]);
    }

    /**
     * 列表查询（含实时过期标记）
     */
    public function getRecordList(array $filters = []): \Illuminate\Support\Collection
    {
        $query = DB::table('sterilization_records')
            ->leftJoin('sterilization_kits', 'sterilization_kits.id', '=', 'sterilization_records.kit_id')
            ->leftJoin('users', 'users.id', '=', 'sterilization_records.operator_id')
            ->whereNull('sterilization_records.deleted_at')
            ->select([
                'sterilization_records.*',
                'sterilization_kits.name as kit_name',
                'sterilization_kits.kit_no',
                'users.name as operator_name',
                DB::raw("
                    CASE
                        WHEN sterilization_records.status = 'used'   THEN 'used'
                        WHEN sterilization_records.expires_at < NOW() THEN 'expired'
                        WHEN sterilization_records.expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiring'
                        ELSE 'valid'
                    END AS display_status
                "),
            ]);

        if (!empty($filters['kit_id'])) {
            $query->where('sterilization_records.kit_id', $filters['kit_id']);
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $query->where('sterilization_records.status', 'valid')
                      ->where('sterilization_records.expires_at', '<', now());
            } elseif ($filters['status'] === 'valid') {
                $query->where('sterilization_records.status', 'valid')
                      ->where('sterilization_records.expires_at', '>=', now());
            } else {
                $query->where('sterilization_records.status', $filters['status']);
            }
        }
        if (!empty($filters['date_from'])) {
            $query->where('sterilization_records.sterilized_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('sterilization_records.sterilized_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->orderBy('sterilization_records.sterilized_at', 'desc')->get();
    }
}
```

- [ ] **Step 5: 跑测试，确认通过**

```bash
php artisan test tests/Feature/SterilizationBatchNoTest.php tests/Feature/SterilizationUsageTest.php
```

期望：PASS (9 tests)

- [ ] **Step 6: 提交**

```bash
git add App/Services/SterilizationService.php \
        tests/Feature/SterilizationBatchNoTest.php \
        tests/Feature/SterilizationUsageTest.php
git commit -m "feat(sterilization): add SterilizationService with batch_no generation and usage tracking"
```

---

## Task 4: SterilizationKitService + SterilizationKitController

**Files:**
- Create: `App/Services/SterilizationKitService.php`
- Create: `App/Http/Controllers/SterilizationKitController.php`

- [ ] **Step 1: 创建 SterilizationKitService**

```php
<?php
// App/Services/SterilizationKitService.php
namespace App\Services;

use App\SterilizationKit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SterilizationKitService
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return SterilizationKit::whereNull('deleted_at')
            ->with('instruments')
            ->orderBy('kit_no')
            ->get();
    }

    public function create(array $data): SterilizationKit
    {
        return DB::transaction(function () use ($data) {
            $kit = SterilizationKit::create([
                'kit_no'    => $data['kit_no'],
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? true,
                '_who_added' => Auth::id(),
            ]);
            $this->syncInstruments($kit, $data['instruments'] ?? []);
            return $kit;
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $kit = SterilizationKit::findOrFail($id);
            $kit->update([
                'kit_no'    => $data['kit_no'],
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);
            $this->syncInstruments($kit, $data['instruments'] ?? []);
            return true;
        });
    }

    public function delete(int $id): bool
    {
        return (bool) SterilizationKit::where('id', $id)->delete();
    }

    /** 先删后插明细（物理删除，无 deleted_at） */
    private function syncInstruments(SterilizationKit $kit, array $instruments): void
    {
        $kit->instruments()->delete();
        foreach ($instruments as $i => $inst) {
            $kit->instruments()->create([
                'instrument_name' => $inst['instrument_name'],
                'quantity'        => $inst['quantity'] ?? 1,
                'sort_order'      => $i,
            ]);
        }
    }
}
```

- [ ] **Step 2: 创建 SterilizationKitController**

```php
<?php
// App/Http/Controllers/SterilizationKitController.php
namespace App\Http\Controllers;

use App\Services\SterilizationKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SterilizationKitController extends Controller
{
    public function __construct(private SterilizationKitService $service)
    {
        $this->middleware('can:view-sterilization');
        $this->middleware('can:manage-sterilization')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $data = $this->service->getAll();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('instruments_count', fn($row) => $row->instruments->count())
                ->addColumn('action', fn($row) => $this->actionButtons($row->id))
                ->rawColumns(['action'])
                ->make(true);
        }
        return response()->json(['status' => 1, 'data' => $this->service->getAll()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_no'                         => 'required|string|max:50|unique:sterilization_kits,kit_no',
            'name'                           => 'required|string|max:100',
            'instruments'                    => 'array',
            'instruments.*.instrument_name'  => 'required|string|max:100',
            'instruments.*.quantity'         => 'integer|min:1',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->create($request->all());
        return response()->json(['status' => 1, 'message' => __('sterilization.kit_created_successfully')]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_no' => "required|string|max:50|unique:sterilization_kits,kit_no,{$id}",
            'name'   => 'required|string|max:100',
            'instruments'                    => 'array',
            'instruments.*.instrument_name'  => 'required|string|max:100',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->update($id, $request->all());
        return response()->json(['status' => 1, 'message' => __('sterilization.kit_updated_successfully')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['status' => 1, 'message' => __('sterilization.kit_deleted_successfully')]);
    }

    public function edit(int $id): JsonResponse
    {
        $kit = \App\SterilizationKit::with('instruments')->findOrFail($id);
        return response()->json($kit);
    }

    private function actionButtons(int $id): string
    {
        return <<<HTML
        <div class="btn-group">
            <button class="btn btn-xs btn-primary" onclick="editKit({$id})">编辑</button>
            <button class="btn btn-xs btn-danger ml-1" onclick="deleteKit({$id})">删除</button>
        </div>
        HTML;
    }
}
```

- [ ] **Step 3: 提交（路由在 Task 7 统一加）**

```bash
git add App/Services/SterilizationKitService.php \
        App/Http/Controllers/SterilizationKitController.php
git commit -m "feat(sterilization): add SterilizationKit CRUD service and controller"
```

---

## Task 5: SterilizationController — 灭菌记录 CRUD + 使用登记 + 导出

**Files:**
- Create: `App/Http/Controllers/SterilizationController.php`

- [ ] **Step 1: 创建 SterilizationController**

```php
<?php
// App/Http/Controllers/SterilizationController.php
namespace App\Http\Controllers;

use App\Services\SterilizationService;
use App\SterilizationKit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SterilizationController extends Controller
{
    public function __construct(private SterilizationService $service)
    {
        $this->middleware('can:view-sterilization');
        $this->middleware('can:manage-sterilization')->only(['store', 'update', 'destroy', 'export']);
    }

    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $filters = $request->only(['kit_id', 'status', 'date_from', 'date_to']);
            $data = $this->service->getRecordList($filters);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('method_label', fn($row) => __('sterilization.method_' . $row->method))
                ->addColumn('status_badge', function ($row) {
                    $map = [
                        'used'     => 'badge-secondary',
                        'expired'  => 'badge-danger',
                        'expiring' => 'badge-warning',
                        'valid'    => 'badge-success',
                    ];
                    $label = __('sterilization.status_' . $row->display_status);
                    $cls   = $map[$row->display_status] ?? 'badge-light';
                    return "<span class='badge {$cls}'>{$label}</span>";
                })
                ->addColumn('action', fn($row) => $this->actionButtons($row))
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $kits = SterilizationKit::where('is_active', true)->whereNull('deleted_at')
            ->orderBy('kit_no')->get(['id', 'kit_no', 'name']);
        return view('sterilization.index', compact('kits'));
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_id'           => 'required|integer|exists:sterilization_kits,id',
            'method'           => 'required|in:autoclave,chemical,dry_heat',
            'temperature'      => 'nullable|numeric|between:0,300',
            'duration_minutes' => 'nullable|integer|min:1',
            'sterilized_at'    => 'required|date',
            'notes'            => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }

        $data = $request->only(['kit_id', 'method', 'temperature', 'duration_minutes', 'sterilized_at', 'notes']);
        $data['operator_id'] = Auth::id();
        $this->service->createRecord($data);

        return response()->json(['status' => 1, 'message' => __('sterilization.record_created_successfully')]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_id'           => 'required|integer|exists:sterilization_kits,id',
            'method'           => 'required|in:autoclave,chemical,dry_heat',
            'temperature'      => 'nullable|numeric',
            'duration_minutes' => 'nullable|integer|min:1',
            'sterilized_at'    => 'required|date',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        try {
            $this->service->updateRecord($id, $request->all());
            return response()->json(['status' => 1, 'message' => __('sterilization.record_updated_successfully')]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        \App\SterilizationRecord::where('id', $id)->delete();
        return response()->json(['status' => 1, 'message' => __('sterilization.record_deleted_successfully')]);
    }

    /** 登记使用 */
    public function use(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'used_at'        => 'required|date',
            'patient_id'     => 'nullable|integer|exists:patients,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'notes'          => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        try {
            $data = $request->only(['used_at', 'patient_id', 'appointment_id', 'notes']);
            $data['used_by'] = Auth::id();
            $this->service->recordUsage($id, $data);
            return response()->json(['status' => 1, 'message' => __('sterilization.usage_recorded_successfully')]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    /** 撤销使用 */
    public function revokeUse(Request $request, int $usageId): JsonResponse
    {
        try {
            $this->service->revokeUsage($usageId);
            return response()->json(['status' => 1, 'message' => __('sterilization.usage_revoked_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    /** 导出 Excel */
    public function export(Request $request)
    {
        // 使用 Maatwebsite\Excel — 实现见 Task 8 补充
        return response()->json(['status' => 0, 'message' => 'TODO: implement export']);
    }

    private function actionButtons(object $row): string
    {
        $useBtn = $row->display_status === 'valid'
            ? "<button class='btn btn-xs btn-info ml-1' onclick='logUse({$row->id})'>登记使用</button>"
            : '';
        $editBtn = $row->display_status === 'valid'
            ? "<button class='btn btn-xs btn-primary' onclick='editRecord({$row->id})'>编辑</button>"
            : '';
        $delBtn = "<button class='btn btn-xs btn-danger ml-1' onclick='deleteRecord({$row->id})'>删除</button>";
        return "<div class='btn-group'>{$editBtn}{$useBtn}{$delBtn}</div>";
    }
}
```

- [ ] **Step 2: 提交**

```bash
git add App/Http/Controllers/SterilizationController.php
git commit -m "feat(sterilization): add SterilizationController with CRUD and usage endpoints"
```

---

## Task 6: 权限 + 菜单 Seeder

**Files:**
- Modify: `database/seeders/PermissionsTableSeeder.php`
- Modify: `database/seeders/MenuItemsSeeder.php`

- [ ] **Step 1: 在 PermissionsTableSeeder 的 `// 诊所事务` 分组新增 2 条权限**

```php
// 诊所事务
['name' => '查看消毒记录', 'slug' => 'view-sterilization',   'module' => '诊所事务', 'description' => '查看灭菌记录和器械包列表，登记使用'],
['name' => '管理消毒记录', 'slug' => 'manage-sterilization', 'module' => '诊所事务', 'description' => '新增/编辑/删除灭菌记录与器械包台账'],
```

- [ ] **Step 2: 运行 Seeder 验证**

```bash
php artisan db:seed --class=PermissionsTableSeeder
```

- [ ] **Step 3: 在 MenuItemsSeeder 加 「诊所事务」一级菜单**

在 `seedMenuTree()` 方法中，在 `$clinicalCenter`（sort_order=30）和 `$opsCenter`（sort_order=40）之间插入：

```php
$clinicAffairs = $this->item(null, 'menu.clinic_affairs', null, 'icon-layers', null, 35, 'SADN');
$this->seedClinicAffairs($clinicAffairs);
```

新增 `seedClinicAffairs()` 方法：

```php
private function seedClinicAffairs(int $parentId): void
{
    $this->item($parentId, 'menu.sterilization_management', 'sterilization', 'icon-shield',
        'view-sterilization', 10, 'SADN');
}
```

> **注意：** `$this->item()` 第 5 参数为 permission slug，只有持有该权限的角色才能看到该菜单项。`SADN` 表示 super-admin / admin / doctor / nurse 都有 view-sterilization 权限时可见。

- [ ] **Step 4: 为角色分配权限（通过 tinker，Seeder 最终版在验收后合并）**

```bash
php artisan tinker --execute="
use App\Permission; use App\Role;

// view-sterilization → SADN
\$viewPerm = Permission::where('slug','view-sterilization')->first();
Role::whereIn('slug',['super-admin','admin','doctor','nurse'])->each(fn(\$r) => \$r->permissions()->syncWithoutDetaching([\$viewPerm->id]));

// manage-sterilization → SADN
\$mgmtPerm = Permission::where('slug','manage-sterilization')->first();
Role::whereIn('slug',['super-admin','admin','doctor','nurse'])->each(fn(\$r) => \$r->permissions()->syncWithoutDetaching([\$mgmtPerm->id]));

echo 'permissions assigned';
"
```

- [ ] **Step 5: 运行 MenuItemsSeeder 验证**

```bash
php artisan db:seed --class=MenuItemsSeeder
php artisan cache:clear
```

访问系统，侧边栏应出现「诊所事务 → 消毒管理」菜单项。

- [ ] **Step 6: 提交**

```bash
git add database/seeders/PermissionsTableSeeder.php \
        database/seeders/MenuItemsSeeder.php
git commit -m "feat(sterilization): add view/manage-sterilization permissions and clinic-affairs menu"
```

---

## Task 7: Routes + i18n

**Files:**
- Modify: `routes/web.php`
- Create: `resources/lang/zh-CN/sterilization.php`
- Create: `resources/lang/en/sterilization.php`
- Modify: `resources/lang/zh-CN/menu.php`
- Modify: `resources/lang/en/menu.php`

- [ ] **Step 1: 在 routes/web.php 加消毒路由**

```php
// ── 消毒管理 ──────────────────────────────────────────────────────────
Route::resource('sterilization-kits', 'SterilizationKitController')
    ->only(['index', 'store', 'update', 'destroy']);
Route::get('sterilization-kits/{id}/edit', 'SterilizationKitController@edit');

Route::resource('sterilization', 'SterilizationController')
    ->only(['index', 'store', 'update', 'destroy']);
Route::post('sterilization/{id}/use',         'SterilizationController@use')
    ->name('sterilization.use');
Route::delete('sterilization-usages/{usageId}/revoke', 'SterilizationController@revokeUse')
    ->name('sterilization.revoke-use');
Route::get('sterilization/export', 'SterilizationController@export')
    ->name('sterilization.export');
```

> **注意：** `sterilization/export` 和 `sterilization/{id}/use` 路由必须在 `Route::resource(...)` 之前注册（或使用命名路由排除），防止 `export` 被 Laravel 解析为 `{id}`。

- [ ] **Step 2: 验证路由**

```bash
php artisan route:list | grep sterilization
```

- [ ] **Step 3: 创建 zh-CN/sterilization.php**

```php
<?php
// resources/lang/zh-CN/sterilization.php
return [
    'records_tab'     => '灭菌记录',
    'kits_tab'        => '器械包管理',

    // 器械包字段
    'kit_no'          => '包号',
    'kit_name'        => '包名称',
    'instruments'     => '器械清单',
    'instrument_name' => '器械名称',
    'quantity'        => '数量',

    // 灭菌记录字段
    'batch_no'        => '批次号',
    'method'          => '灭菌方式',
    'method_autoclave'  => '高压蒸汽',
    'method_chemical'   => '化学消毒',
    'method_dry_heat'   => '干热灭菌',
    'temperature'     => '温度(℃)',
    'duration_minutes' => '时长(分钟)',
    'operator'        => '操作员',
    'sterilized_at'   => '灭菌时间',
    'expires_at'      => '有效期至',

    // 状态
    'status_valid'    => '有效',
    'status_used'     => '已使用',
    'status_expired'  => '已过期',
    'status_expiring' => '即将过期',
    'status_voided'   => '已作废',

    // 使用登记
    'log_use'         => '登记使用',
    'used_at'         => '使用时间',
    'used_by'         => '操作医生',
    'usage_notes'     => '备注',
    'revoke_use'      => '撤销使用',

    // 成功/错误消息
    'record_created_successfully' => '灭菌记录创建成功',
    'record_updated_successfully' => '灭菌记录更新成功',
    'record_deleted_successfully' => '灭菌记录删除成功',
    'kit_created_successfully'    => '器械包创建成功',
    'kit_updated_successfully'    => '器械包更新成功',
    'kit_deleted_successfully'    => '器械包删除成功',
    'usage_recorded_successfully' => '使用记录登记成功',
    'usage_revoked_successfully'  => '使用记录已撤销',
];
```

- [ ] **Step 4: 在 zh-CN/menu.php 追加**

```php
'clinic_affairs'           => '诊所事务',
'sterilization_management' => '消毒管理',
```

- [ ] **Step 5: 提交**

```bash
git add routes/web.php \
        resources/lang/zh-CN/sterilization.php \
        resources/lang/en/sterilization.php \
        resources/lang/zh-CN/menu.php \
        resources/lang/en/menu.php
git commit -m "feat(sterilization): add routes and i18n files"
```

---

## Task 8: 视图 — 主页面 + 5 个局部视图

**Files:**
- Create: `resources/views/sterilization/index.blade.php`
- Create: `resources/views/sterilization/_tab_records.blade.php`
- Create: `resources/views/sterilization/_tab_kits.blade.php`
- Create: `resources/views/sterilization/_modal_record.blade.php`
- Create: `resources/views/sterilization/_modal_kit.blade.php`
- Create: `resources/views/sterilization/_modal_use.blade.php`

- [ ] **Step 1: 创建 index.blade.php**

```blade
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sterilization.css') }}">
@endsection

@section('content')
<div class="page-content">
    <div class="page-header">
        <h3>{{ __('menu.sterilization_management') }}</h3>
    </div>

    <ul class="nav nav-tabs" id="sterilizationTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tab-records" role="tab">
                {{ __('sterilization.records_tab') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-kits" role="tab">
                {{ __('sterilization.kits_tab') }}
            </a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="tab-records" role="tabpanel">
            @include('sterilization._tab_records')
        </div>
        <div class="tab-pane fade" id="tab-kits" role="tabpanel">
            @include('sterilization._tab_kits')
        </div>
    </div>
</div>

@include('sterilization._modal_record')
@include('sterilization._modal_kit')
@include('sterilization._modal_use')
@endsection

@section('js')
<script>
LanguageManager.loadFromPHP(@json(__('sterilization')), 'sterilization');
const sterilizationKits = @json($kits);
</script>
<script src="{{ asset('include_js/sterilization.js') }}?v={{ filemtime(public_path('include_js/sterilization.js')) }}"></script>
@endsection
```

- [ ] **Step 2: 创建 _tab_records.blade.php**

```blade
{{-- 筛选栏 --}}
<div class="row mb-3">
    <div class="col-md-3">
        <select class="form-control select2" id="filter-kit-id">
            <option value="">{{ __('common.all') }}（器械包）</option>
            @foreach($kits as $kit)
            <option value="{{ $kit->id }}">{{ $kit->kit_no }} - {{ $kit->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-control" id="filter-status">
            <option value="">{{ __('common.all') }}（状态）</option>
            <option value="valid">{{ __('sterilization.status_valid') }}</option>
            <option value="used">{{ __('sterilization.status_used') }}</option>
            <option value="expired">{{ __('sterilization.status_expired') }}</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" class="form-control" id="filter-date-from" placeholder="开始日期">
    </div>
    <div class="col-md-2">
        <input type="date" class="form-control" id="filter-date-to" placeholder="结束日期">
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary" id="btn-filter-records">{{ __('common.search') }}</button>
        @can('manage-sterilization')
        <button class="btn btn-success ml-1" id="btn-add-record">{{ __('common.add') }}</button>
        <a class="btn btn-secondary ml-1" href="{{ route('sterilization.export') }}">{{ __('common.export') }}</a>
        @endcan
    </div>
</div>

<table id="records-datatable" class="table table-bordered table-hover w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('sterilization.batch_no') }}</th>
            <th>器械包</th>
            <th>{{ __('sterilization.method') }}</th>
            <th>{{ __('sterilization.sterilized_at') }}</th>
            <th>{{ __('sterilization.expires_at') }}</th>
            <th>{{ __('sterilization.operator') }}</th>
            <th>状态</th>
            <th>{{ __('common.action') }}</th>
        </tr>
    </thead>
</table>
```

- [ ] **Step 3: 创建 _tab_kits.blade.php**

```blade
@can('manage-sterilization')
<button class="btn btn-success mb-2" id="btn-add-kit">{{ __('common.add') }}</button>
@endcan

<table id="kits-datatable" class="table table-bordered table-hover w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('sterilization.kit_no') }}</th>
            <th>{{ __('sterilization.kit_name') }}</th>
            <th>器械数量</th>
            <th>状态</th>
            <th>{{ __('common.action') }}</th>
        </tr>
    </thead>
</table>
```

- [ ] **Step 4: 创建 _modal_record.blade.php**

```blade
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="record-modal-title">新增灭菌记录</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="record-id">
                <div class="form-group">
                    <label>{{ __('sterilization.kit_name') }} *</label>
                    <select class="form-control select2" id="record-kit-id">
                        {{-- JS 动态注入 sterilizationKits --}}
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('sterilization.method') }} *</label>
                    <select class="form-control" id="record-method">
                        <option value="autoclave">{{ __('sterilization.method_autoclave') }}</option>
                        <option value="chemical">{{ __('sterilization.method_chemical') }}</option>
                        <option value="dry_heat">{{ __('sterilization.method_dry_heat') }}</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>{{ __('sterilization.temperature') }}</label>
                        <input type="number" step="0.1" class="form-control" id="record-temperature">
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ __('sterilization.duration_minutes') }}</label>
                        <input type="number" class="form-control" id="record-duration">
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('sterilization.sterilized_at') }} *</label>
                    <input type="datetime-local" class="form-control" id="record-sterilized-at">
                </div>
                <div class="form-group">
                    <label>备注</label>
                    <textarea class="form-control" id="record-notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-record">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: 创建 _modal_kit.blade.php**

包含：kit_no, name, is_active，以及动态器械明细行（instrument_name + quantity + 删除按钮 + 「添加器械」按钮）。

```blade
<div class="modal fade" id="kitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kit-modal-title">新增器械包</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="kit-id">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>{{ __('sterilization.kit_no') }} *</label>
                        <input type="text" class="form-control" id="kit-no" placeholder="KIT-001">
                    </div>
                    <div class="form-group col-md-8">
                        <label>{{ __('sterilization.kit_name') }} *</label>
                        <input type="text" class="form-control" id="kit-name">
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <strong>{{ __('sterilization.instruments') }}</strong>
                    <button type="button" class="btn btn-xs btn-outline-primary" id="btn-add-instrument">
                        + 添加器械
                    </button>
                </div>
                <table class="table table-sm" id="instruments-table">
                    <thead>
                        <tr>
                            <th>器械名称</th>
                            <th width="100">数量</th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody id="instruments-body">
                        {{-- JS 动态添加行 --}}
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-kit">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 6: 创建 _modal_use.blade.php**

```blade
<div class="modal fade" id="useModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('sterilization.log_use') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="use-record-id">

                {{-- 只读展示批次信息 --}}
                <div class="alert alert-info p-2 mb-3" id="use-record-info">
                    批次号：<strong id="use-batch-no"></strong>
                    &nbsp;|&nbsp; 器械包：<strong id="use-kit-name"></strong>
                </div>

                <div class="form-group">
                    <label>{{ __('sterilization.used_at') }} *</label>
                    <input type="datetime-local" class="form-control" id="use-used-at">
                </div>

                {{-- 搜索关联患者（Select2 AJAX） --}}
                <div class="form-group">
                    <label>关联患者（可选）</label>
                    <select class="form-control select2-patient" id="use-patient-id">
                        <option value="">不关联患者</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>{{ __('sterilization.usage_notes') }}</label>
                    <textarea class="form-control" id="use-notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-success" id="btn-confirm-use">确认登记</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 7: 提交**

```bash
git add resources/views/sterilization/
git commit -m "feat(sterilization): add sterilization views with record/kit/use modals"
```

---

## Task 9: sterilization.js + sterilization.css

**Files:**
- Create: `public/include_js/sterilization.js`
- Create: `public/css/sterilization.css`

- [ ] **Step 1: 创建 sterilization.js**

```javascript
// public/include_js/sterilization.js
'use strict';

let recordsTable = null;
let kitsTable    = null;

$(document).ready(function () {
    initKitSelectOptions();
    initRecordsTable();
    initKitsTable();
    bindRecordModal();
    bindKitModal();
    bindUseModal();
    bindFilters();
});

/* ── 1. 初始化器械包下拉 ───────────────────────────── */
function initKitSelectOptions() {
    if (typeof sterilizationKits === 'undefined') return;
    const $sel = $('#record-kit-id');
    sterilizationKits.forEach(function (kit) {
        $sel.append(`<option value="${kit.id}">${kit.kit_no} - ${kit.name}</option>`);
    });
    $sel.select2({ width: '100%' });
}

/* ── 2. 灭菌记录 DataTable ───────────────────────── */
function initRecordsTable() {
    recordsTable = $('#records-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/sterilization',
            data: function (d) {
                d.kit_id    = $('#filter-kit-id').val() || null;
                d.status    = $('#filter-status').val() || null;
                d.date_from = $('#filter-date-from').val() || null;
                d.date_to   = $('#filter-date-to').val() || null;
            },
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'batch_no' },
            { data: 'kit_name' },
            { data: 'method_label' },
            { data: 'sterilized_at' },
            { data: 'expires_at' },
            { data: 'operator_name' },
            { data: 'status_badge', orderable: false },
            { data: 'action', orderable: false },
        ],
        language: { url: '/vendor/datatables/zh-CN.json' },
    });
}

/* ── 3. 器械包 DataTable ─────────────────────────── */
function initKitsTable() {
    kitsTable = $('#kits-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/sterilization-kits',
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'kit_no' },
            { data: 'name' },
            { data: 'instruments_count' },
            { data: 'is_active', render: function (v) { return v ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-secondary">停用</span>'; } },
            { data: 'action', orderable: false },
        ],
        language: { url: '/vendor/datatables/zh-CN.json' },
    });
}

/* ── 4. 筛选条件 ──────────────────────────────────── */
function bindFilters() {
    $('#btn-filter-records').click(function () {
        recordsTable.ajax.reload();
    });
}

/* ── 5. 灭菌记录弹框 ──────────────────────────────── */
function bindRecordModal() {
    $('#btn-add-record').click(function () {
        resetRecordModal();
        $('#record-modal-title').text(LanguageManager.trans('common.add'));
        $('#recordModal').modal('show');
    });

    $('#btn-save-record').click(function () {
        const id  = $('#record-id').val();
        const url = id ? `/sterilization/${id}` : '/sterilization';
        $.ajax({
            url, method: id ? 'PUT' : 'POST',
            data: {
                _token:           $('meta[name="csrf-token"]').attr('content'),
                kit_id:           $('#record-kit-id').val(),
                method:           $('#record-method').val(),
                temperature:      $('#record-temperature').val() || null,
                duration_minutes: $('#record-duration').val() || null,
                sterilized_at:    $('#record-sterilized-at').val(),
                notes:            $('#record-notes').val(),
            },
            success: function (res) {
                if (res.status) {
                    $('#recordModal').modal('hide');
                    recordsTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

function resetRecordModal() {
    $('#record-id').val('');
    $('#record-kit-id').val(null).trigger('change');
    $('#record-method').val('autoclave');
    $('#record-temperature, #record-duration, #record-notes').val('');
    $('#record-sterilized-at').val(new Date().toISOString().slice(0, 16));
}

function editRecord(id) {
    $.get(`/sterilization/${id}/edit`, function (data) {
        $('#record-id').val(data.id);
        $('#record-kit-id').val(data.kit_id).trigger('change');
        $('#record-method').val(data.method);
        $('#record-temperature').val(data.temperature);
        $('#record-duration').val(data.duration_minutes);
        $('#record-sterilized-at').val(data.sterilized_at ? data.sterilized_at.replace(' ', 'T').slice(0, 16) : '');
        $('#record-notes').val(data.notes);
        $('#record-modal-title').text(LanguageManager.trans('common.edit'));
        $('#recordModal').modal('show');
    });
}

function deleteRecord(id) {
    Swal.fire({ title: '确认删除此灭菌记录?', icon: 'warning', showCancelButton: true })
        .then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/sterilization/${id}`, method: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        if (res.status) { recordsTable.ajax.reload(); toastr.success(res.message); }
                        else { toastr.error(res.message); }
                    },
                });
            }
        });
}

/* ── 6. 登记使用弹框 ──────────────────────────────── */
function bindUseModal() {
    $('#btn-confirm-use').click(function () {
        const id = $('#use-record-id').val();
        $.ajax({
            url: `/sterilization/${id}/use`,
            method: 'POST',
            data: {
                _token:     $('meta[name="csrf-token"]').attr('content'),
                used_at:    $('#use-used-at').val(),
                patient_id: $('#use-patient-id').val() || null,
                notes:      $('#use-notes').val(),
            },
            success: function (res) {
                if (res.status) {
                    $('#useModal').modal('hide');
                    recordsTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

function logUse(recordId) {
    // 先获取记录信息回填弹框
    $.get(`/sterilization/${recordId}/edit`, function (data) {
        $('#use-record-id').val(recordId);
        $('#use-batch-no').text(data.batch_no);
        $('#use-kit-name').text(data.kit_name || '');
        $('#use-used-at').val(new Date().toISOString().slice(0, 16));
        $('#use-patient-id').val(null).trigger('change');
        $('#use-notes').val('');
        $('#useModal').modal('show');
    });
}

/* ── 7. 器械包弹框 ───────────────────────────────── */
function bindKitModal() {
    $('#btn-add-kit').click(function () {
        resetKitModal();
        $('#kit-modal-title').text(LanguageManager.trans('common.add'));
        $('#kitModal').modal('show');
    });

    $('#btn-add-instrument').click(function () {
        addInstrumentRow('', 1);
    });

    $('#btn-save-kit').click(function () {
        const id  = $('#kit-id').val();
        const url = id ? `/sterilization-kits/${id}` : '/sterilization-kits';
        const instruments = [];
        $('#instruments-body tr').each(function () {
            instruments.push({
                instrument_name: $(this).find('.instrument-name').val(),
                quantity:        $(this).find('.instrument-qty').val(),
            });
        });
        $.ajax({
            url, method: id ? 'PUT' : 'POST',
            data: {
                _token:      $('meta[name="csrf-token"]').attr('content'),
                kit_no:      $('#kit-no').val(),
                name:        $('#kit-name').val(),
                instruments: instruments,
            },
            traditional: false,
            success: function (res) {
                if (res.status) {
                    $('#kitModal').modal('hide');
                    kitsTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

function resetKitModal() {
    $('#kit-id, #kit-no, #kit-name').val('');
    $('#instruments-body').empty();
}

function addInstrumentRow(name, qty) {
    const row = `<tr>
        <td><input type="text" class="form-control form-control-sm instrument-name" value="${name}"></td>
        <td><input type="number" class="form-control form-control-sm instrument-qty" value="${qty}" min="1"></td>
        <td><button type="button" class="btn btn-xs btn-danger" onclick="$(this).closest('tr').remove()">×</button></td>
    </tr>`;
    $('#instruments-body').append(row);
}

function editKit(id) {
    $.get(`/sterilization-kits/${id}/edit`, function (data) {
        $('#kit-id').val(data.id);
        $('#kit-no').val(data.kit_no);
        $('#kit-name').val(data.name);
        $('#instruments-body').empty();
        (data.instruments || []).forEach(function (inst) {
            addInstrumentRow(inst.instrument_name, inst.quantity);
        });
        $('#kit-modal-title').text(LanguageManager.trans('common.edit'));
        $('#kitModal').modal('show');
    });
}

function deleteKit(id) {
    Swal.fire({ title: '确认删除此器械包?', icon: 'warning', showCancelButton: true })
        .then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/sterilization-kits/${id}`, method: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        if (res.status) { kitsTable.ajax.reload(); toastr.success(res.message); }
                        else { toastr.error(res.message); }
                    },
                });
            }
        });
}
```

- [ ] **Step 2: 创建 sterilization.css**

```css
/* public/css/sterilization.css */

/* 状态徽章颜色补充 */
.badge-warning  { color: #856404; background-color: #fff3cd; }
.badge-danger   { color: #721c24; background-color: #f8d7da; }
.badge-success  { color: #155724; background-color: #d4edda; }
.badge-secondary { color: #6c757d; background-color: #e9ecef; }

/* 使用登记弹框：批次信息展示 */
#use-record-info {
    font-size: 13px;
}

/* 器械明细表格紧凑 */
#instruments-table td {
    vertical-align: middle;
    padding: 4px 6px;
}
```

- [ ] **Step 3: SterilizationController 补 edit 方法**（用于 JS `$.get` 回填弹框）

在 `SterilizationController` 中追加：

```php
public function edit(int $id): JsonResponse
{
    $record = \App\SterilizationRecord::with('kit')->findOrFail($id);
    return response()->json(array_merge($record->toArray(), [
        'kit_name' => $record->kit->name ?? null,
    ]));
}
```

在 routes/web.php 追加：

```php
Route::get('sterilization/{id}/edit', 'SterilizationController@edit');
```

- [ ] **Step 4: 浏览器手工测试清单**

- [ ] 访问 `/sterilization`，确认「诊所事务 → 消毒管理」菜单可见
- [ ] 灭菌记录 Tab：DataTable 正常加载
- [ ] 新增灭菌记录弹框：填写并保存，批次号格式 `S20260323-001`
- [ ] 状态列：valid/expired/expiring 显示正确颜色
- [ ] 登记使用：点击「登记使用」弹框出现，确认后状态变「已使用」
- [ ] 撤销使用：在 used 记录上操作，状态回滚为 valid
- [ ] 器械包 Tab：新增包含 2 个器械的器械包，编辑正常

- [ ] **Step 5: 跑所有消毒相关测试**

```bash
php artisan test tests/Feature/SterilizationBatchNoTest.php tests/Feature/SterilizationUsageTest.php
```

- [ ] **Step 6: 提交**

```bash
git add public/include_js/sterilization.js \
        public/css/sterilization.css \
        App/Http/Controllers/SterilizationController.php \
        routes/web.php
git commit -m "feat(sterilization): complete sterilization module - JS, CSS, edit endpoint"
```

---

## Task 10: 最终联调验收

- [ ] **Step 1: 全量测试**

```bash
php artisan test tests/Feature/SterilizationBatchNoTest.php \
                 tests/Feature/SterilizationUsageTest.php
```

- [ ] **Step 2: 权限联调**

用不同角色账号登录（admin / doctor / nurse），确认：
- admin: 可见「诊所事务」，可新增记录、管理器械包
- doctor: 可见「诊所事务」，可登记使用 ✓，可新增灭菌记录 ✓
- nurse: 可见「诊所事务」，可新增灭菌记录 ✓
- receptionist: **不可见**「诊所事务」菜单

- [ ] **Step 3: 最终 commit**

```bash
git add .
git commit -m "feat(sterilization): sterilization module complete - full traceability chain"
```

---

## 实现顺序摘要

```
Task 1  (迁移)
Task 2  (模型)
Task 3  (SterilizationService + 测试 TDD)  ← 核心业务逻辑，优先验证
Task 4  (SterilizationKitService + Controller)
Task 5  (SterilizationController)
Task 6  (权限 + 菜单 Seeder)
Task 7  (路由 + i18n)
Task 8  (视图)
Task 9  (JS + CSS)
Task 10 (联调验收)
```
