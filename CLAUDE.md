# Dental Medical Management System

## System Overview

This is a comprehensive dental medical management system built with Laravel framework, featuring modular architecture for different user roles (Doctor, Nurse, Receptionist, SuperAdmin). composer.json:47-56

## Architecture

### Modular Structure

The system uses Laravel Modules package for modular architecture: modules.php:16-17

```
Modules/  
├── Doctor/  
│   ├── Http/Controllers/  
│   │   ├── AppointmentsController.php  
│   │   ├── DoctorClaimController.php  
│   │   └── DoctorController.php  
│   └── Resources/views/  
├── Nurse/  
├── Receptionist/  
└── SuperAdmin/  
```

### Core Controllers

- **AppointmentsController**: Manages appointments with DataTables and FullCalendar integration AppointmentsController.php:277-296
- **PatientController**: Handles patient management with search and filtering PatientController.php:28-60
- **InvoiceController**: Manages invoicing and payments InvoiceController.php:43-90

## Setup Instructions

### Prerequisites

- PHP 8.2+
- MySQL 5.7+
- Composer
- Node.js (for assets)
- Framework: Laravel 11.x

### Installation

1. **Clone and install dependencies**:

```
composer install  
npm install
```

1. **Environment setup**:

```
cp .env.example .env  
php artisan key:generate
```

1. **Database configuration**:
   Configure database connection in `.env` file
2. **Run migrations and seeders**:

```
php artisan migrate  
php artisan db:seed
```

1. **Start development server**:

```
php artisan serve
```

## Database Schema

### Key Tables

- **users**: User authentication and role management
- **patients**: Patient information and medical history
- **appointments**: Appointment scheduling with `sort_by` field for ordering AppointmentsController.php:277-289
- **invoices**: Billing and payment management
- **doctor_claims**: Doctor commission claims

### Important Fields

- `sort_by` in appointments table: DateTime field for sorting appointments
- `is_doctor` in users table: Enum('Yes', 'No') to identify doctors
- `deleted_at`: Soft deletes implementation across tables

## Routing

All routes are defined in `routes/web.php` with authentication middleware: web.php:23-228

Key route groups:

- `/patients` - Patient management
- `/appointments` - Appointment scheduling
- `/invoices` - Billing system
- `/doctor-appointments` - Doctor-specific appointments

## Authentication & Authorization

### Role-Based Access Control

System implements role-based permissions via `AuthServiceProvider`:

Roles:

- Super Administrator
- Administrator
- Doctor
- Nurse
- Receptionist

### Permission System

Extended with database-driven permissions:

- `permissions` table: Define individual permissions
- `role_permissions` table: Map permissions to roles
- Middleware protection: `->middleware('can:permission-slug')`

## Internationalization (i18n)

### Supported Languages

- English (default)
- Chinese (zh-CN)

### Language Files Structure

```
resources/lang/  
├── zh-CN/  
│   ├── auth.php  
│   ├── pagination.php  
│   ├── validation.php  
│   ├── common.php  
│   └── tasks.php  
└── modules/  
    ├── doctor/zh-CN/  
    ├── nurse/zh-CN/  
    ├── receptionist/zh-CN/  
    └── superadmin/zh-CN/  
```

### JavaScript i18n

Frontend translations via `LanguageManager.trans()` function:

```
// Example usage  
const message = LanguageManager.trans('common.save');  
const validation = LanguageManager.trans('validation.max', {max: 10});
```

## Common Issues & Solutions

### Missing Database Fields

Several fields may be missing from initial migrations:

1. **is_doctor field**:

```
php artisan make:migration add_is_doctor_to_users_table
```

1. **sort_by field for appointments**:

```
php artisan make:migration add_sort_by_to_appointments_table
```

1. **price field for invoice_items**:

```
php artisan make:migration add_price_to_invoice_items_table
```

### Circular Foreign Key Dependencies

When seeding data, resolve circular dependencies between `users` and `branches` tables by making `_who_added` nullable:

### DataTables AJAX Errors

Ensure controller methods return proper JSON responses:

```
return Datatables::of($data)->make(true);
```

## Frontend Components

### DataTables Integration

All list views use Yajra DataTables with server-side processing: index.blade.php:128-144

### FullCalendar

Appointment calendar visualization using FullCalendar.

### Bootstrap Components

- Datepicker with localization
- Select2 with Chinese translations
- WYSIHTML5 editor

## API Endpoints

### RESTful Resources

Most controllers follow RESTful patterns:

- `GET /resource` - List
- `POST /resource` - Create
- `GET /resource/{id}` - Show
- `PUT /resource/{id}` - Update
- `DELETE /resource/{id}` - Delete

### Custom Endpoints

- `/search-patient` - Patient search
- `/export-invoices` - Invoice export
- `/doctor-performance-report` - Performance analytics

## Development Guidelines

### Code Patterns

1. **Validation**: Use `Validator::make()` for input validation AppointmentsController.php:200-202
2. **JSON Responses**: Standard format with `message` and `status` fields AppointmentsController.php:155-158
3. **Soft Deletes**: Include `whereNull('deleted_at')` in queries
4. **Authentication**: Use `Auth::User()->id` for user-specific data

### Testing

Run tests with:

```
php artisan test
```

## Deployment

### Production Setup

1. Set environment to production:

```
APP_ENV=production  
APP_DEBUG=false
```

1. Optimize application:

```
php artisan config:cache  
php artisan route:cache  
php artisan view:cache
```

1. Set up file permissions:

```markdown
chmod -R 755 storage  
chmod -R 755 bootstrap/cache
```