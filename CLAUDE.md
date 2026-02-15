# Dental Medical Management System

## Overview

Laravel 11.x dental clinic management system with modular architecture (Doctor, Nurse, Receptionist, SuperAdmin, Pharmacy).

- **PHP**: 8.2+
- **Database**: MySQL 5.7+ (135 tables, soft deletes via `deleted_at`)
- **i18n**: zh-CN (primary), en

## Architecture

### Modular Structure (`nwidart/laravel-modules` v11)

```
Modules/
├── Doctor/
├── Nurse/
├── Receptionist/
├── SuperAdmin/
└── Pharmacy/
```

Each module has its own `Http/Controllers/`, `Resources/views/`, and route files.

### Key Tables

| Table                        | Purpose                              |
| ---------------------------- | ------------------------------------ |
| `users`                      | Auth & roles, `is_doctor` enum       |
| `patients`                   | Patient info & medical history       |
| `appointments`               | Scheduling, `sort_by` for ordering   |
| `invoices` / `invoice_items` | Billing                              |
| `doctor_claims`              | Doctor commissions                   |

## Authentication

### Web (Session)

Role-based via `AuthServiceProvider`:

- Super Administrator, Administrator, Doctor, Nurse, Receptionist
- Middleware: `->middleware('can:permission-slug')`

### API (Sanctum)

Token + SPA cookie authentication at `/api/v1/`.

```php
// Login (no auth required)
POST /api/v1/auth/login   {email, password}

// Authenticated endpoints
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

## API v1 Endpoints

Base: `/api/v1/` | Auth: `auth:sanctum` | Response: `{success, data, message, meta?}`

### Patients

```
GET    /patients           # List (paginated)
POST   /patients           # Create
GET    /patients/{id}      # Show
PUT    /patients/{id}      # Update
DELETE /patients/{id}      # Soft delete
GET    /patients/search?q= # Search by name/phone
GET    /patients/{id}/medical-history
```

### Appointments

```
GET    /appointments
POST   /appointments
GET    /appointments/{id}
PUT    /appointments/{id}
DELETE /appointments/{id}
GET    /appointments/calendar-events  # FullCalendar format
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

## Routing

- Web routes: [routes/web.php](routes/web.php)
- API v1 routes: [routes/api/v1.php](routes/api/v1.php)

Key web prefixes: `/patients`, `/appointments`, `/invoices`, `/doctor-appointments`

## i18n

### Structure

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

### JavaScript

```javascript
LanguageManager.trans('common.save');
LanguageManager.trans('validation.max', {max: 10});
```

## Code Patterns

### Validation

```php
$validator = Validator::make($request->all(), [
    'field' => 'required|string|max:255',
]);
if ($validator->fails()) {
    return response()->json(['message' => $validator->errors()->first(), 'status' => 0]);
}
```

### JSON Response

```php
return response()->json([
    'message' => 'Success',
    'status'  => 1,
    'data'    => $result,
]);
```

### Soft Deletes

Always include `whereNull('deleted_at')` or use model's `SoftDeletes` trait.

## Frontend

- **DataTables**: Yajra server-side processing
- **Calendar**: FullCalendar for appointments
- **UI**: Bootstrap, Select2, Datepicker (all with zh-CN locale)

## Quick Start

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
# Configure DB in .env
php artisan migrate --seed
php artisan serve
```

## Architecture Principles

- Module dependencies flow: `app/` → `Modules/*`. Never create reverse dependencies.
- When unsure about class placement, verify the module dependency graph before proposing.
- For cross-module interfaces, prefer Service Provider or event-based decoupling.
- One class = one responsibility. Do not merge multiple unrelated concerns into a single class.

## Implementation Approach

- When given a task, **implement actual code changes**. Do not stop at planning/exploration unless explicitly asked to only plan.
- Bias toward action over analysis. If a plan exists and is approved, proceed to implementation immediately.
- Never spend an entire session exploring and planning when the user asked for implementation.

## Debugging & Root Cause Analysis

- When diagnosing errors from logs, trace the **full call chain** through config/template/data before blaming code logic.
- Do not guess at root causes. Verify by reading the actual configuration, template definitions, and runtime data paths.
- If the user pushes back on a diagnosis, start fresh from the evidence rather than defending the initial theory.

## Code Changes Discipline

- After editing files, always run the project build/compile command to verify changes before reporting completion.
- Never remove existing imports, annotations (`@Transactional`, providers, aliases) unless you have explicitly verified they are unused.
- When refactoring, check for post-refactor breakages in dependent files before marking the task as done.

## Design Document Edits

- When asked to rewrite or fix a section in a design document, rewrite the **entire section** as requested. Do not attempt inline patches unless explicitly told to.
- Respect the user's language preferences — this project uses Chinese (中文) for documentation and UI labels. Never substitute technical English terms where Chinese labels are expected.

## Technology Stack

- **Backend**: PHP 8.2+, Laravel 11.x, MySQL 5.7+
- **Frontend**: Bootstrap, jQuery, Yajra DataTables, FullCalendar, Select2
- **Packages**: nwidart/laravel-modules v11, laravel/sanctum v4, barryvdh/laravel-dompdf v2, maatwebsite/excel v3.1
- Always verify version compatibility before proposing dependency changes.
