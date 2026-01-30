---
description: Add translation key to EN and ZH-CN language files
---

# Add Translation Key Command

Add a new translation key to both English and Chinese language files.

## Task

Add a new translation key with its English and Chinese translations to the appropriate language files.

## Input Format

The user can provide input in several formats:

### Format 1: Structured Input
```
file: messages
key: record_saved_successfully
EN: Record saved successfully
ZH: 记录保存成功
```

### Format 2: Dot Notation
```
messages.record_saved_successfully
EN: Record saved successfully
ZH: 记录保存成功
```

### Format 3: Quick Format
```
messages.record_saved_successfully = Record saved successfully / 记录保存成功
```

### Format 4: Nested Keys
```
invoices.status.paid
EN: Paid
ZH: 已支付
```

### Format 5: Module Translation (with namespace)
```
doctor::appointments.title
EN: Doctor Appointments
ZH: 医生预约
```

## Process

### Step 1: Parse Input
Extract the file name, key path, and translations from the input.

### Step 2: Locate Language Files

**Standard files:**
- `resources/lang/en/{file}.php`
- `resources/lang/zh-CN/{file}.php`

**Module files (if namespace provided):**
- `Modules/{Module}/Resources/lang/en/{file}.php`
- `Modules/{Module}/Resources/lang/zh-CN/{file}.php`

### Step 3: Check for Existing Key
Read both files and check if the key already exists. If it does:
- Warn the user
- Ask if they want to update the existing value

### Step 4: Add Translation Key
Insert the new key in both files:
- For simple keys: add at the end of the array or in alphabetical order
- For nested keys: create the nested structure if it doesn't exist

### Step 5: Validate Syntax
Run `php -l` on both modified files to ensure no syntax errors.

### Step 6: Confirm Changes
Show the changes made and provide usage examples.

---

## Supported Language Files

### Core Application Files

| File                | Description                                      |
|---------------------|--------------------------------------------------|
| `common`            | Shared UI elements (buttons, labels, statuses)   |
| `messages`          | Controller response messages                     |
| `validation`        | Validation rules and custom messages             |
| `auth`              | Authentication messages                          |
| `invoices`          | Invoice/billing related                          |
| `patient`           | Patient management                               |
| `appointment`       | Appointment scheduling                           |
| `medical_treatment` | Treatment related                                |
| `doctor_claims`     | Doctor claims                                    |
| `sms`               | SMS message templates                            |
| `emails`            | Email templates                                  |
| `holidays`          | Holiday management                               |
| `expenses`          | Expense management                               |
| `leaves`            | Leave requests                                   |
| `roles`             | Role management                                  |
| `permissions`       | Permission management                            |
| `report`            | Reports                                          |
| `medical`           | Medical records                                  |

Path pattern: `resources/lang/{locale}/{file}.php`

### Module Translation Files

| Module       | Namespace        | Path                                             |
|--------------|------------------|--------------------------------------------------|
| Doctor       | `doctor::`       | `Modules/Doctor/Resources/lang/{locale}/`        |
| Nurse        | `nurse::`        | `Modules/Nurse/Resources/lang/{locale}/`         |
| Receptionist | `receptionist::` | `Modules/Receptionist/Resources/lang/{locale}/`  |
| SuperAdmin   | `superadmin::`   | `Modules/SuperAdmin/Resources/lang/{locale}/`    |

---

## Translation Patterns

### Simple Translation
```php
// English
'save' => 'Save'

// Chinese
'save' => '保存'
```

### Parameterized Translation
Use `:parameter` syntax for dynamic values:
```php
// English
'greeting' => 'Hello, :name! Your appointment is on :date.'

// Chinese
'greeting' => '您好，:name！您的预约日期是 :date。'
```

### Nested Translation
```php
// English
'status' => [
    'paid' => 'Paid',
    'unpaid' => 'Unpaid',
    'partial' => 'Partially Paid',
],

// Chinese
'status' => [
    'paid' => '已支付',
    'unpaid' => '未支付',
    'partial' => '部分支付',
],
```

### Pluralization (Laravel style)
```php
// English
'items_count' => '{0} No items|{1} One item|[2,*] :count items'

// Chinese
'items_count' => '{0} 没有项目|{1} 1 个项目|[2,*] :count 个项目'
```

---

## Key Naming Conventions

| Type            | Pattern              | Example                          |
|-----------------|----------------------|----------------------------------|
| Success message | `action_successfully`| `invoice_created_successfully`   |
| Error message   | `error_description`  | `error_invalid_input`            |
| Confirmation    | `confirm_action`     | `confirm_delete_invoice`         |
| Button text     | `action`             | `save`, `cancel`, `delete`       |
| Status label    | `status_name`        | `active`, `inactive`, `pending`  |
| Form label      | `field_name`         | `patient_name`, `phone_number`   |
| Placeholder     | `enter_field`        | `enter_email_here`               |
| Title           | `title`              | `invoice_list`, `patient_details`|

---

## Batch Add (Multiple Keys)

You can add multiple keys at once:

```
file: invoices

keys:
- invoice_void: Void Invoice / 作废发票
- invoice_voided_successfully: Invoice voided successfully / 发票作废成功
- confirm_void_invoice: Are you sure you want to void this invoice? / 确定要作废此发票吗？
```

---

## Error Handling

### Key Already Exists
If the key already exists, the command will:
1. Show the current value
2. Ask for confirmation to update
3. Only update if confirmed

### Invalid File
If the specified file doesn't exist:
1. Ask if the user wants to create a new language file
2. Create with proper PHP array structure if confirmed

### Syntax Error After Edit
If `php -l` fails:
1. Show the error
2. Revert the changes
3. Report the issue to the user

---

## Output

After successful addition, report:

1. **Files modified:**
   - `resources/lang/en/{file}.php`
   - `resources/lang/zh-CN/{file}.php`

2. **Key added:**
   ```php
   'key_name' => 'English text'  // EN
   'key_name' => '中文文本'       // ZH-CN
   ```

3. **Validation results:**
   ```
   ✓ resources/lang/en/{file}.php - No syntax errors
   ✓ resources/lang/zh-CN/{file}.php - No syntax errors
   ```

4. **Usage example:**
   ```php
   // In PHP/Controller
   __('file.key_name')

   // In Blade
   {{ __('file.key_name') }}

   // With parameters
   __('file.key_name', ['param' => $value])
   ```

---

## Examples

### Example 1: Add Simple Key
**Input:**
```
common.loading
EN: Loading...
ZH: 加载中...
```

**Output:**
```
Added to resources/lang/en/common.php:
    'loading' => 'Loading...',

Added to resources/lang/zh-CN/common.php:
    'loading' => '加载中...',

Usage: {{ __('common.loading') }}
```

### Example 2: Add Parameterized Key
**Input:**
```
messages.welcome_user
EN: Welcome back, :name!
ZH: 欢迎回来，:name！
```

**Output:**
```
Added to resources/lang/en/messages.php:
    'welcome_user' => 'Welcome back, :name!',

Added to resources/lang/zh-CN/messages.php:
    'welcome_user' => '欢迎回来，:name！',

Usage: {{ __('messages.welcome_user', ['name' => $user->name]) }}
```

### Example 3: Add Nested Key
**Input:**
```
invoices.payment.status.pending
EN: Payment Pending
ZH: 待支付
```

**Output:**
```
Added to resources/lang/en/invoices.php:
    'payment' => [
        'status' => [
            'pending' => 'Payment Pending',
        ],
    ],

Usage: {{ __('invoices.payment.status.pending') }}
```

### Example 4: Add Module Translation
**Input:**
```
doctor::dashboard.welcome_message
EN: Welcome to Doctor Dashboard
ZH: 欢迎来到医生仪表板
```

**Output:**
```
Added to Modules/Doctor/Resources/lang/en/dashboard.php:
    'welcome_message' => 'Welcome to Doctor Dashboard',

Added to Modules/Doctor/Resources/lang/zh-CN/dashboard.php:
    'welcome_message' => '欢迎来到医生仪表板',

Usage: {{ __('doctor::dashboard.welcome_message') }}
```