# 患者管理增强方案

> 基于口腔云(EasyDent)对标分析，按优先级排列。每个方案独立，可单独决定是否实施。

---

## P0-1：批量操作（标签 / 分组）

### 现状
- 列表页无勾选框，无批量操作入口
- 标签/分组只能在**患者详情页**逐个修改（左侧面板 AJAX 自动保存）
- DataTable 已有完整的筛选管道（标签、分组、来源、日期等）

### 目标
列表页勾选多个患者 → 批量：设置标签、移入分组、更改分组、移出分组

### 方案

#### 1. 前端改动 (`index.blade.php` + JS)

**a) DataTable 增加勾选列**
```
columns 首列加 checkbox:
☐ | # | 姓名 | 性别 | 电话 | 标签 | 来源 | 医保 | 操作
```
- 表头全选 checkbox
- 每行 checkbox，值为 patient.id
- 已选数量实时显示在工具栏

**b) 工具栏增加批量操作按钮**
在"导出"按钮旁新增下拉按钮「批量操作」，包含：
- 设置标签（弹窗多选标签 → 追加/覆盖二选一）
- 移入分组（弹窗选分组）
- 移出分组（确认后清空选中患者的 patient_group）

按钮默认 disabled，选中 ≥1 个患者后激活。

**c) 弹窗**
- **批量设置标签弹窗**：Select2 多选标签 + 模式选择（追加到现有标签 / 替换现有标签）
- **批量移入分组弹窗**：Radio 单选分组列表

#### 2. 后端改动

**新增路由** (`routes/web.php`)：
```php
Route::post('patients/batch-tags', 'PatientController@batchUpdateTags')->middleware('can:edit-patients');
Route::post('patients/batch-group', 'PatientController@batchUpdateGroup')->middleware('can:edit-patients');
```

**PatientController 新增方法**：
```php
public function batchUpdateTags(Request $request)
{
    // 验证: patient_ids (array, required), tag_ids (array), mode (append|replace)
    // 遍历 patient_ids，对每个患者：
    //   mode=append → patientTags()->syncWithoutDetaching($tagIds)
    //   mode=replace → patientTags()->sync($tagIds)
    // 返回 {status: 1, message: '已更新 N 位患者的标签'}
}

public function batchUpdateGroup(Request $request)
{
    // 验证: patient_ids (array, required), group_code (string|null)
    // Patient::whereIn('id', $ids)->update(['patient_group' => $groupCode])
    // 返回 {status: 1, message: '已更新 N 位患者的分组'}
}
```

#### 3. 涉及文件

| 文件 | 改动 |
|------|------|
| `resources/views/patients/index.blade.php` | 加 checkbox 列、批量按钮、两个弹窗 |
| `public/include_js/patient_list.js`（或 index 内联 JS） | checkbox 管理、批量 AJAX 调用 |
| `app/Http/Controllers/PatientController.php` | +2 方法 |
| `routes/web.php` | +2 路由 |
| `resources/lang/zh-CN/patient.php` | 新增批量操作翻译 key |
| `resources/lang/en/patient.php` | 同上 |

#### 4. 工作量估算
- 前端：~200 行 (HTML + JS)
- 后端：~60 行
- 翻译：~10 个 key

---

## P0-2：患者合并 UI

### 现状
- 数据库已有：`patients.merged_to_id` (FK)、`patients.status` (enum: active/merged/archived)
- Model 已有：`mergedTo()`、`mergedPatients()`、`scopeMerged()` 关系
- **无 UI、无 Controller 方法、无 Service 方法**

### 目标
在列表页选择 2 个患者 → 弹窗对比信息 → 选择主记录 → 合并（副记录标记为 merged，关联数据迁移到主记录）

### 方案

#### 1. 交互流程

```
列表页勾选 2 个患者 → 点击"合并患者"按钮
    ↓
打开合并弹窗（全屏或大弹窗）
    ├── 左侧：患者 A 基本信息
    ├── 右侧：患者 B 基本信息
    ├── 每行字段可选择保留 A 或 B 的值（冲突字段高亮）
    └── 底部：选择"以 A 为主" 或 "以 B 为主"（默认更早创建的为主）
    ↓
确认合并 → 后端执行
    ├── 主记录：更新选择的字段值
    ├── 副记录：status='merged', merged_to_id=主记录ID
    ├── 关联迁移：预约、账单、病历、图像、回访 → 全部指向主记录
    └── 标签合并：两个患者的标签取并集
```

#### 2. 后端改动

**新增路由**：
```php
Route::post('patients/merge-preview', 'PatientController@mergePreview')->middleware('can:edit-patients');
Route::post('patients/merge', 'PatientController@mergePatients')->middleware('can:edit-patients');
```

**PatientService 新增方法**：

```php
// 获取两个患者的对比数据
public function getMergePreview(int $idA, int $idB): array

// 执行合并（事务）
public function mergePatients(int $primaryId, int $secondaryId, array $fieldOverrides): bool
{
    // DB::transaction 内执行：
    // 1. 更新主记录的指定字段（用户选择的值）
    // 2. 迁移关联表：
    //    - appointments: patient_id → primaryId
    //    - invoices: patient_id → primaryId
    //    - medical_cases: patient_id → primaryId
    //    - patient_images: patient_id → primaryId
    //    - patient_followups: patient_id → primaryId
    // 3. 标签取并集: sync 两者 tag_ids 的 union
    // 4. 副记录: status='merged', merged_to_id=primaryId
    // 5. AccessLog 记录合并操作
}
```

#### 3. 前端改动

**新增文件**：
- `resources/views/patients/modals/merge.blade.php` — 合并对比弹窗
- 对比弹窗中左右两列显示患者信息，冲突字段（值不同）用黄色高亮，每行有 Radio 选择保留哪个值

**列表页改动**：
- 工具栏批量操作菜单增加「合并患者」选项（仅勾选恰好 2 人时可用）

#### 4. 需要迁移的关联表

| 表 | 外键字段 | 备注 |
|----|---------|------|
| `appointments` | `patient_id` | |
| `invoices` | `patient_id` | |
| `medical_cases` | `patient_id` | |
| `patient_images` | `patient_id` | |
| `patient_followups` | `patient_id` | |
| `patient_tag_pivot` | `patient_id` | 合并后去重 |
| `member_shared_holders` | `patient_id` | 亲属关系 |
| `doctor_claims` | `patient_id` | 如有 |

#### 5. 涉及文件

| 文件 | 改动 |
|------|------|
| `resources/views/patients/index.blade.php` | 批量菜单加"合并"项 |
| `resources/views/patients/modals/merge.blade.php` | **新建** |
| `public/include_js/patient_merge.js` | **新建** |
| `app/Http/Controllers/PatientController.php` | +2 方法 |
| `app/Services/PatientService.php` | +2 方法 |
| `routes/web.php` | +2 路由 |
| `resources/lang/*/patient.php` | 新增翻译 key |

#### 6. 风险点
- **数据完整性**：合并必须在事务中执行，任何一步失败全部回滚
- **会员系统**：如果两个患者都有会员卡，需要人工决定保留哪张（弹窗提示）
- **不可逆性**：建议合并前生成快照记录，存入 `patient_merge_logs` 表备查

---

## P1-1：Excel 批量导入

### 现状
- 已有 `PatientExport`（基于 `maatwebsite/excel` v3.1）
- **无 PatientImport 类**
- 模板下载可复用导出的表头结构

### 目标
下载导入模板 → 填写患者数据 → 上传 Excel → 校验 → 预览/确认 → 批量创建

### 方案

#### 1. 交互流程

```
工具栏「导入」按钮 → 弹窗
    ├── 步骤1：下载模板 (Excel，含表头 + 示例行 + 字段说明 sheet)
    ├── 步骤2：上传文件 (拖拽或选择 .xlsx/.xls/.csv)
    ├── 步骤3：预览校验结果
    │       ├── 绿色行：校验通过，可导入
    │       ├── 红色行：有错误（手机号重复/格式错误等），标注具体错误字段
    │       └── 显示统计：共 N 行，通过 M 行，失败 K 行
    └── 步骤4：确认导入（仅导入通过的行，跳过失败行）
```

#### 2. 后端改动

**新增路由**：
```php
Route::get('patients/import-template', 'PatientController@downloadImportTemplate')->middleware('can:create-patients');
Route::post('patients/import-preview', 'PatientController@importPreview')->middleware('can:create-patients');
Route::post('patients/import', 'PatientController@importPatients')->middleware('can:create-patients');
```

**新增类 `App/Imports/PatientImport.php`**：
```php
// 实现 ToArray / WithHeadingRow / WithValidation
// 校验规则：
//   - 姓名: required
//   - 手机号: required, 格式校验, 唯一性检查(patients.phone_no)
//   - 性别: in:男,女,Male,Female
//   - 身份证号: 可选, 格式校验(15/18位)
//   - 出生日期: 可选, date 格式
//   - 邮箱: 可选, email 格式
```

**新增类 `App/Exports/PatientImportTemplate.php`**：
```php
// 生成导入模板
// Sheet1：数据填写区（带表头）
// Sheet2：填写说明（字段说明 + 示例数据 + 注意事项）
```

**PatientController 新增方法**：
```php
public function downloadImportTemplate()
{
    return Excel::download(new PatientImportTemplate, '患者导入模板.xlsx');
}

public function importPreview(Request $request)
{
    // 1. 接收上传文件
    // 2. 解析 Excel 到数组
    // 3. 逐行校验（不入库）
    // 4. 返回: {total, valid_count, invalid_count, rows: [{row_num, data, errors}]}
}

public function importPatients(Request $request)
{
    // 1. 再次解析+校验上传文件
    // 2. 过滤掉有错误的行
    // 3. 批量创建患者（事务）
    // 4. 返回: {imported_count, skipped_count}
}
```

#### 3. 导入字段映射

| 模板表头 | DB 字段 | 必填 | 校验规则 |
|---------|---------|------|---------|
| 姓名 | surname (+ othername) | 是 | max:100 |
| 手机号 | phone_no | 是 | 唯一性校验 |
| 性别 | gender | 是 | 男/女 |
| 身份证号 | nin | 否 | 15/18位格式 |
| 出生日期 | date_of_birth | 否 | Y-m-d 格式 |
| 邮箱 | email | 否 | email 格式 |
| 地址 | address | 否 | max:255 |
| 血型 | blood_type | 否 | A/B/AB/O/未知 |
| 职业 | profession | 否 | max:100 |
| 紧急联系人 | next_of_kin | 否 | max:100 |
| 紧急联系电话 | next_of_kin_phone | 否 | 手机格式 |
| 备注 | remark | 否 | max:500 |

#### 4. 重复处理策略
- **手机号重复**：标记为错误行，不导入（提示"手机号 XXX 已存在，患者编号 YYY"）
- **身份证号重复**：标记为警告，仍可导入（因为可能不同人）
- **姓名重复**：不作为冲突依据（同名常见）

#### 5. 涉及文件

| 文件 | 改动 |
|------|------|
| `app/Imports/PatientImport.php` | **新建** |
| `app/Exports/PatientImportTemplate.php` | **新建** |
| `app/Http/Controllers/PatientController.php` | +3 方法 |
| `routes/web.php` | +3 路由 |
| `resources/views/patients/index.blade.php` | 工具栏加"导入"按钮 |
| `resources/views/patients/modals/import.blade.php` | **新建** — 分步弹窗 |
| `public/include_js/patient_import.js` | **新建** |
| `resources/lang/*/patient.php` | 新增翻译 key |

---

## P1-2：高级查询增强

### 现状
高级筛选区有：医保公司、标签(多选)、日期范围
侧边栏有：分组筛选、标签筛选

### 目标
增加 3 个高频查询维度（参考口腔云高级查询弹窗）：
1. **年龄范围**（min ~ max）
2. **消费金额范围**（min ~ max）
3. **首诊医生**（Select2 下拉）

### 方案

#### 1. 前端改动 (`index.blade.php`)

在现有高级筛选区（`#advancedFilters`）追加一行：

```html
<!-- 第三行：新增筛选条件 -->
<div class="col-md-3">
    <label>年龄范围</label>
    <div class="input-group input-group-sm">
        <input type="number" id="filter_age_min" min="0" max="120" placeholder="最小">
        <span class="input-group-addon">~</span>
        <input type="number" id="filter_age_max" min="0" max="120" placeholder="最大">
    </div>
</div>
<div class="col-md-3">
    <label>消费金额范围</label>
    <div class="input-group input-group-sm">
        <input type="number" id="filter_spend_min" min="0" step="0.01" placeholder="最低">
        <span class="input-group-addon">~</span>
        <input type="number" id="filter_spend_max" min="0" step="0.01" placeholder="最高">
    </div>
</div>
<div class="col-md-3">
    <label>首诊医生</label>
    <select id="filter_doctor" class="form-control select2">
        <option value="">全部</option>
    </select>
</div>
```

**DataTable AJAX data 函数追加参数**：
```javascript
d.filter_age_min = $('#filter_age_min').val();
d.filter_age_max = $('#filter_age_max').val();
d.filter_spend_min = $('#filter_spend_min').val();
d.filter_spend_max = $('#filter_spend_max').val();
d.filter_doctor = $('#filter_doctor').val();
```

#### 2. 后端改动 (`PatientService::getPatientList`)

在现有筛选逻辑后追加：

```php
// 年龄范围（基于 date_of_birth 计算）
if (!empty($filters['filter_age_min'])) {
    $maxDob = now()->subYears((int) $filters['filter_age_min'])->format('Y-m-d');
    $query->where('patients.date_of_birth', '<=', $maxDob);
}
if (!empty($filters['filter_age_max'])) {
    $minDob = now()->subYears((int) $filters['filter_age_max'] + 1)->format('Y-m-d');
    $query->where('patients.date_of_birth', '>', $minDob);
}

// 消费金额范围（子查询 invoices 合计）
if (!empty($filters['filter_spend_min']) || !empty($filters['filter_spend_max'])) {
    $query->whereExists(function ($sub) use ($filters) {
        $sub->select(DB::raw(1))
            ->from('invoices')
            ->whereColumn('invoices.patient_id', 'patients.id')
            ->whereNull('invoices.deleted_at')
            ->groupBy('invoices.patient_id')
            ->when(!empty($filters['filter_spend_min']), fn($q) =>
                $q->havingRaw('SUM(invoices.total) >= ?', [$filters['filter_spend_min']])
            )
            ->when(!empty($filters['filter_spend_max']), fn($q) =>
                $q->havingRaw('SUM(invoices.total) <= ?', [$filters['filter_spend_max']])
            );
    });
}

// 首诊医生
if (!empty($filters['filter_doctor'])) {
    $query->whereExists(function ($sub) use ($filters) {
        $sub->select(DB::raw(1))
            ->from('appointments')
            ->whereColumn('appointments.patient_id', 'patients.id')
            ->where('appointments.doctor_id', $filters['filter_doctor'])
            ->whereNull('appointments.deleted_at')
            ->orderBy('appointments.start_date')
            ->limit(1);
    });
}
```

#### 3. 涉及文件

| 文件 | 改动 |
|------|------|
| `resources/views/patients/index.blade.php` | 高级筛选区 +3 字段，JS data 函数 +5 参数，Select2 初始化 |
| `app/Services/PatientService.php` | `getPatientList()` 追加 3 段筛选逻辑 |
| `app/Http/Controllers/PatientController.php` | `index()` 的 `$request->only()` 数组追加 5 个 key |
| `resources/lang/*/patient.php` | +5 翻译 key |

#### 4. 注意事项
- 年龄筛选依赖 `date_of_birth` 字段有值，空值患者不会被年龄筛选命中
- 消费金额子查询可能较慢，建议在 `invoices` 表的 `(patient_id, deleted_at)` 上确保有索引
- 首诊医生的 Select2 使用 AJAX 加载（复用已有的 `/search-doctors` 接口）

---

## 总结对照表

| 方案 | 优先级 | 新建文件 | 改动文件 | 复杂度 |
|------|--------|---------|---------|--------|
| 批量操作（标签/分组） | P0 | 0 | 4 + 翻译 | 低 |
| 患者合并 UI | P0 | 3 | 4 + 翻译 | 中高 |
| Excel 批量导入 | P1 | 4 | 3 + 翻译 | 中 |
| 高级查询增强 | P1 | 0 | 3 + 翻译 | 低 |
