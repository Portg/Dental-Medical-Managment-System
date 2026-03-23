# 设计文档：收费项目管理升级 + 消毒登记功能

**日期**：2026-03-23
**状态**：已确认，待实现
**涉及模块**：系统管理 → 收费项目、诊所事务 → 消毒管理

---

## 一、背景与目标

### 1.1 收费项目管理升级

现有 `clinic-services` 页面仅有项目名称、价格、录入人三列，无分类体系、无批量操作、无套餐管理。参照竞品（口腔云 8.6.1~8.6.8）的布局样式，升级为具备以下能力的完整收费项目管理页：

- **A** 收费大类树 + 项目列表升级（单位、允许折扣、常用标记、显示开关）
- **B** 批量改价 + Excel 批量导入
- **C** 常用项目标记（划价时快速筛选）
- **D** 收费套餐（多项目打包定价）

### 1.2 消毒登记（全追溯）

系统完全缺失消毒功能，属于监管合规刚需（卫生局检查必查项）。目标实现完整追溯链：器械包台账 → 灭菌批次 → 使用记录（关联患者就诊）。

---

## 二、Anti-Goals（不做的事）

- 不实现医保价格、报销比例字段（当前业务不涉及医保）
- 不实现收费项目的版本历史追溯
- 不实现消毒模块的移动端独立应用
- 消毒使用记录不做强制关联预约（appointment_id 可空）
- 不实现 8.6.2「清空价目表」功能（破坏性操作，风险高）
- 不实现 8.6.8 预约项目设置（与预约模块改造不在本期范围）

---

## 三、数据模型

### 3.1 收费项目管理（新增 3 张表，修改 1 张）

#### 新增：`service_categories`（收费大类）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| name | string(50) | 大类名称，唯一 |
| sort_order | integer | 显示顺序，默认 0 |
| is_active | boolean | 是否显示，默认 true |
| _who_added | bigint FK→users | |
| created_at / updated_at / deleted_at | timestamps | 软删除 |

#### 修改：`medical_services`（新增字段）

| 新增字段 | 类型 | 说明 |
|----------|------|------|
| category_id | bigint FK→service_categories nullable | 所属大类 |
| is_discountable | boolean | 允许打折，默认 true |
| is_favorite | boolean | 常用项目，默认 false |
| sort_order | integer | 大类内排序，默认 0 |

#### 新增：`service_packages`（收费套餐）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| name | string(100) | 套餐名称 |
| description | text nullable | 说明 |
| total_price | decimal(12,2) | 套餐总价 |
| is_active | boolean | 是否启用，默认 true |
| _who_added | bigint FK→users | |
| created_at / updated_at / deleted_at | timestamps | |

#### 新增：`service_package_items`（套餐明细，中间表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| package_id | bigint FK→service_packages | |
| service_id | bigint FK→medical_services | |
| qty | integer | 数量，默认 1 |
| price | decimal(12,2) | 套餐内单价（可与原价不同） |
| sort_order | integer | |

---

### 3.2 消毒登记（全新 4 张表）

#### `sterilization_kits`（器械包台账）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| kit_no | string(50) unique | 包号（如 KIT-001） |
| name | string(100) | 包名称 |
| is_active | boolean | 是否启用 |
| _who_added | bigint FK→users | |
| created_at / updated_at / deleted_at | timestamps | |

#### `sterilization_kit_instruments`（器械包明细）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| kit_id | bigint FK→sterilization_kits | |
| instrument_name | string(100) | 器械名称 |
| quantity | integer | 数量，默认 1 |
| sort_order | integer | 显示顺序 |

#### `sterilization_records`（灭菌批次）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| kit_id | bigint FK→sterilization_kits | |
| batch_no | string(50) unique | 批次号（格式：S{YYYYMMDD}-{NNN}，自动生成） |
| method | enum | 高压蒸汽 / 化学消毒 / 干热灭菌 |
| temperature | decimal(5,1) nullable | 温度（℃） |
| duration_minutes | integer nullable | 时长（分钟） |
| operator_id | bigint FK→users | 操作员 |
| sterilized_at | datetime | 灭菌时间 |
| expires_at | datetime | 有效期至（默认 +90 天） |
| status | enum | 有效 / 已使用 / 已过期 |
| notes | text nullable | 备注 |
| created_at / updated_at / deleted_at | timestamps | |

#### `sterilization_usages`（使用追溯）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint PK | |
| record_id | bigint FK→sterilization_records | |
| appointment_id | bigint FK→appointments nullable | 关联预约（可空） |
| patient_id | bigint FK→patients nullable | 关联患者（可空） |
| used_by | bigint FK→users | 操作医生 |
| used_at | datetime | 使用时间 |
| notes | text nullable | |
| patient_name | string(100) nullable | 患者姓名快照（冗余） |
| doctor_name | string(100) nullable | 医生姓名快照（冗余） |
| kit_name | string(100) nullable | 器械包名称快照（冗余） |
| batch_no | string(50) nullable | 批次号快照（冗余） |
| created_at / updated_at | timestamps | |

> **冗余字段说明**：写入时由 Service 层自动从关联记录填充，无需前端传入。用于导出报表免 JOIN、保障历史记录在患者/医生被软删除后仍完整可读。

---

## 四、路由设计

### 4.1 收费项目管理（Web）

```
GET    /clinic-services                    # 项目管理主页（Tab: 项目管理 / 收费套餐）
POST   /clinic-services                    # 新增项目
PUT    /clinic-services/{id}               # 更新项目
DELETE /clinic-services/{id}              # 删除项目（软删除）
POST   /clinic-services/batch-update-price # 批量改价
POST   /clinic-services/import             # Excel 导入
GET    /clinic-services/export             # 导出 Excel

GET    /service-categories                 # 大类列表（JSON，供树形菜单用）
POST   /service-categories                 # 新增大类
PUT    /service-categories/{id}            # 更新大类
DELETE /service-categories/{id}           # 删除大类
POST   /service-categories/reorder        # 拖拽排序

GET    /service-packages                   # 套餐列表
POST   /service-packages                   # 新增套餐
PUT    /service-packages/{id}              # 更新套餐
DELETE /service-packages/{id}             # 删除套餐
```

### 4.2 消毒管理（Web）

```
GET    /sterilization                      # 灭菌记录列表
POST   /sterilization                      # 新增灭菌记录
PUT    /sterilization/{id}                 # 更新记录
DELETE /sterilization/{id}                # 软删除
GET    /sterilization/export               # 导出 Excel
POST   /sterilization/{id}/use             # 登记使用（创建 usage）

GET    /sterilization-kits                 # 器械包列表
POST   /sterilization-kits                 # 新增器械包
PUT    /sterilization-kits/{id}            # 更新器械包
DELETE /sterilization-kits/{id}           # 软删除
```

---

## 五、控制器 / Service 层

### 5.1 收费项目管理

| 类 | 职责 |
|----|------|
| `ServiceCategoryController` | 大类 CRUD + 排序 |
| `MedicalServiceController`（改造） | 升级列表页、新增批量改价/导入方法 |
| `ServicePackageController` | 套餐 CRUD |
| `MedicalServiceService`（改造） | 新增 `batchUpdatePrice()`, `importFromExcel()`, `getExportData()` |
| `ServiceCategoryService`（新建） | 大类 CRUD + 树形数据组装 |
| `ServicePackageService`（新建） | 套餐 CRUD |

### 5.2 消毒管理

| 类 | 职责 |
|----|------|
| `SterilizationKitController` | 器械包 + 明细 CRUD |
| `SterilizationController` | 灭菌记录 CRUD、登记使用、导出 |
| `SterilizationKitService` | 器械包台账逻辑 |
| `SterilizationService` | 批次号生成、有效期计算、冗余字段自动填充、过期状态更新 |

**批次号生成规则**：`S{YYYYMMDD}-{NNN}`，每日从 001 自增，例：`S20260323-001`。

**有效期默认规则**：高压蒸汽 / 干热 → +90 天；化学消毒 → +30 天（可在系统设置中配置）。

---

## 六、视图结构

### 6.1 收费项目管理页（`resources/views/clinical_services/index.blade.php` 全面改写）

```
clinic-services/
├── index.blade.php           # 主页面（Tab: 项目管理 / 收费套餐）
├── _tab_services.blade.php   # 项目管理 Tab（左侧大类树 + 右侧 DataTable）
├── _tab_packages.blade.php   # 收费套餐 Tab
├── _modal_service.blade.php  # 新增/编辑项目弹框
├── _modal_package.blade.php  # 新增/编辑套餐弹框
└── _modal_import.blade.php   # Excel 导入弹框
```

JS 文件：`public/include_js/clinic_services.js`
CSS 文件：`public/css/clinic_services.css`

### 6.2 消毒管理页（全新）

```
sterilization/
├── index.blade.php           # 主页面（Tab: 灭菌记录 / 器械包管理）
├── _tab_records.blade.php    # 灭菌记录 Tab（DataTable + 筛选栏）
├── _tab_kits.blade.php       # 器械包管理 Tab
├── _modal_record.blade.php   # 新增/编辑灭菌记录弹框
├── _modal_kit.blade.php      # 新增/编辑器械包弹框（含器械明细动态行）
└── _modal_use.blade.php      # 登记使用弹框（关联预约搜索 + 冗余字段自动填充）
```

JS 文件：`public/include_js/sterilization.js`
CSS 文件：`public/css/sterilization.css`

---

## 七、导航菜单变更

### 新增一级菜单：「诊所事务」

| 字段 | 值 |
|------|----|
| title_key | `menu.clinic_affairs` |
| icon | `icon-layers` |
| sort_order | 35（插在诊疗中心=30 和运营中心=40 之间） |
| 可见角色 | SuperAdmin, Admin, Nurse |

### 新增二级菜单：「消毒管理」

| 字段 | 值 |
|------|----|
| parent | 诊所事务 |
| title_key | `menu.sterilization_management` |
| url | `sterilization` |
| permission | `manage-sterilization`（新增权限） |
| 可见角色 | SuperAdmin, Admin, Nurse |

### i18n 新增键

```php
// zh-CN/menu.php
'clinic_affairs'            => '诊所事务',
'sterilization_management'  => '消毒管理',

// zh-CN/sterilization.php（新文件）
'records_tab'    => '灭菌记录',
'kits_tab'       => '器械包管理',
'batch_no'       => '批次号',
'method'         => '灭菌方式',
'method_autoclave'  => '高压蒸汽',
'method_chemical'   => '化学消毒',
'method_dry_heat'   => '干热灭菌',
'sterilized_at'  => '灭菌时间',
'expires_at'     => '有效期至',
'status_valid'   => '有效',
'status_used'    => '已使用',
'status_expired' => '已过期',
'status_expiring' => '即将过期',
'log_use'        => '登记使用',
```

---

## 八、权限设计

| 权限 slug | 说明 | 默认角色 |
|-----------|------|---------|
| `manage-sterilization` | 消毒管理（增删改查） | SuperAdmin, Admin, Nurse |
| `manage-service-categories` | 收费大类管理 | SuperAdmin, Admin |
| `manage-service-packages` | 收费套餐管理 | SuperAdmin, Admin |
| `import-medical-services` | 批量导入收费项目 | SuperAdmin, Admin |

---

## 九、实现顺序（建议）

1. **迁移文件**：4 张新表 + medical_services 加字段
2. **模型 + Service 层**：SterilizationKit, SterilizationRecord, SterilizationUsage, ServiceCategory, ServicePackage
3. **收费项目管理**：路由 → 控制器 → 视图（左树右表布局 + 套餐 Tab）
4. **消毒管理**：路由 → 控制器 → 视图（灭菌记录 + 器械包 Tab）
5. **导航菜单**：MenuItemsSeeder 新增条目 + i18n
6. **批量导入 / 导出**：Excel 模板 + Maatwebsite\Excel 实现
7. **权限**：PermissionSeeder 新增条目
