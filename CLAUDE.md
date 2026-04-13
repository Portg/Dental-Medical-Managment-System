# Dental Medical Management System

面向 AI 与协作者的**项目速查**：技术栈、目录约定、API 摘要、前后端规范与协作原则。更细的流程见仓库内 `.claude/skills/`。

## 速览

| 项 | 说明 |
| --- | --- |
| 后端 | PHP 8.2+，Laravel 11.x |
| 数据库 | MySQL 5.7+（约 135 张表，软删除用 `deleted_at`） |
| 前端 | Bootstrap 4、jQuery、Laravel Mix；页面 CSS/JS 与 Blade 分离（见下文） |
| 语言 | 界面与文档以 **zh-CN** 为主，英文 `en` 为辅 |

## 技术栈（关键依赖）

- **Laravel**：`laravel/framework` ^11、`laravel/sanctum` ^4、`laravel/ui` ^4
- **数据与报表**：`yajra/laravel-datatables-oracle` ^11、`maatwebsite/excel` ^3.1、`barryvdh/laravel-dompdf` ^2
- **其他**：`owen-it/laravel-auditing`、`spatie/laravel-backup`、`consoletvs/charts` 等（以 `composer.json` 为准）

> 升级或新增依赖前，请在 `composer.json` / `package.json` 中核对版本兼容性。

## 架构

### 应用结构（单体 `app/`）

业务代码集中在 **`app/`**（`Http/Controllers/`、`Models/`、`Services/` 等），按**角色与业务域**组织（如医生/护士/前台/药房相关控制器与仪表盘），**不再使用** `nwidart/laravel-modules` 的 `Modules/*` 目录（若历史文档仍提及模块包，以当前仓库为准）。

### 核心业务表（节选）

| 表 | 用途 |
| --- | --- |
| `users` | 认证与角色，`is_doctor` 等 |
| `patients` | 患者信息与病史 |
| `appointments` | 预约，含 `sort_by` 等 |
| `invoices` / `invoice_items` | 账单 |
| `doctor_claims` | 医生提成 |

## 认证

### Web（Session）

基于角色与权限：`AuthServiceProvider`，中间件示例 `->middleware('can:permission-slug')`。常见角色含：超级管理员、管理员、医生、护士、前台等。

### API（Sanctum）

基址 **`/api/v1/`**，Token / SPA Cookie。示例：

```php
// 无需认证
POST /api/v1/auth/login   { "email", "password" }

// 需 auth:sanctum
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

## API v1 摘要

基址：`/api/v1/` | 中间件：`auth:sanctum` | 响应形态：`{ success, data, message, meta? }`（与具体控制器实现保持一致）

### Patients

```
GET    /patients
POST   /patients
GET    /patients/{id}
PUT    /patients/{id}
DELETE /patients/{id}
GET    /patients/search?q=
GET    /patients/{id}/medical-history
```

### Appointments

```
GET    /appointments
POST   /appointments
GET    /appointments/{id}
PUT    /appointments/{id}
DELETE /appointments/{id}
GET    /appointments/calendar-events
GET    /appointments/chairs
GET    /appointments/doctor-time-slots?doctor_id=&date=
POST   /appointments/{id}/reschedule
```

### Invoices

```
GET    /invoices
POST   /invoices
GET    /invoices/{id}
DELETE /invoices/{id}
GET    /invoices/search?q=
GET    /invoices/{id}/amount
GET    /invoices/{id}/procedures
POST   /invoices/{id}/approve-discount
POST   /invoices/{id}/reject-discount
POST   /invoices/{id}/set-credit
```

### Medical Cases

```
GET    /medical-cases
POST   /medical-cases
GET    /medical-cases/{id}
PUT    /medical-cases/{id}
DELETE /medical-cases/{id}
GET    /medical-cases/icd10-search?q=
GET    /medical-cases/patient/{patientId}
```

## 路由入口

| 类型 | 文件 |
| --- | --- |
| Web | [routes/web.php](routes/web.php) |
| API v1 | [routes/api/v1.php](routes/api/v1.php) |

常见 Web 前缀示例：`/patients`、`/appointments`、`/invoices`、`/doctor-appointments`（以实际路由为准）。

## 国际化（i18n）

### 目录

```
resources/lang/
├── en/
├── zh-CN/
│   ├── common.php
│   ├── validation.php
│   └── ...
└── modules/
    ├── doctor/zh-CN/
    ├── nurse/zh-CN/
    └── ...
```

### 前端 JS

```javascript
LanguageManager.trans('common.save');
LanguageManager.trans('validation.max', { max: 10 });
```

页面级翻译可在 `@section('js')` 顶部注入：`LanguageManager.loadFromPHP(@json(__('module')), 'module')`。`common` 与 `validation` 若在布局中已全局加载，勿重复注入。

## 前端规范（Blade / CSS / JS）

- **DataTables**：Yajra 服务端模式  
- **日历**：FullCalendar（预约等）  
- **UI**：Bootstrap、Select2、Datepicker（中文 locale）

### 资源拆分（强制）

Blade 中**禁止**大段内联 `<style>` / `<script>`，须拆到独立文件：

| 类型 | 路径 | 引入 |
| --- | --- | --- |
| CSS | `public/css/<page-name>.css` | `asset('css/<page-name>.css')` |
| JS | `public/include_js/<page_name>.js` | `asset('include_js/<page_name>.js')` + `?v={{ filemtime(...) }}` |

Blade 中仅保留结构与 `@section` / `@yield`；JS 内用 `LanguageManager.trans`，避免在 JS 里写 Blade `{{ __() }}`。

## 代码片段约定

### 校验

```php
$validator = Validator::make($request->all(), [
    'field' => 'required|string|max:255',
]);
if ($validator->fails()) {
    return response()->json(['message' => $validator->errors()->first(), 'status' => 0]);
}
```

### JSON 响应（示例）

```php
return response()->json([
    'message' => 'Success',
    'status'  => 1,
    'data'    => $result,
]);
```

### 软删除

查询时包含 `whereNull('deleted_at')` 或使用模型 `SoftDeletes` trait。

## 本地与验证

### 首次启动

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
# 配置 .env 数据库
php artisan migrate --seed
php artisan serve
```

### 修改代码后建议执行

```bash
php artisan test
# 若修改了前端打包资源
npm run production
```

（以实际 CI 与团队约定为准。）

## 架构原则

- 依赖方向：`app/` 为核心；**不要**让底层模块反向依赖不合理边界（跨域协作优先用 Service、事件或明确接口）。
- 类职责单一；不确定类归属时，先对照现有分层再改。
- 跨模块协作优先 **Service Provider** 或 **事件** 解耦。

## 实现方式（给 AI）

- 用户要求实现功能时，**直接改代码**；除非用户明确只要方案或调研，否则不要整轮只停留在规划。
- 已有确认方案时，优先落地实现。

## 调试与根因

- 从日志排查时，沿 **配置 → 模板 → 数据 → 代码** 追踪完整调用链，再下结论。
- 避免猜测；用实际配置与运行路径验证。
- 若用户质疑结论，基于证据重新查，而非固执原结论。

## 变更纪律

- 提交前运行项目约定的构建/测试命令，确认无报错再宣称完成。
- 未核实前勿删 import、注解（如 `@Transactional`）、Provider 注册等。
- 重构后检查依赖该代码的调用方。

## 设计文档

- 用户要求重写某章节时，**整段重写**该章节；除非用户明确要求，否则不要只做零碎补丁。
- 文档与 UI 标签用**中文**；需要中文标签处勿用英文技术术语替代。
