# 数据库初始化指南（中国地区版本）

本文档提供牙科医疗管理系统数据库的完整初始化说明，适用于中国地区部署。

---

## 📋 目录

1. [前置要求](#前置要求)
2. [数据库初始化方法](#数据库初始化方法)
3. [初始化脚本说明](#初始化脚本说明)
4. [默认账户信息](#默认账户信息)
5. [故障排除](#故障排除)
6. [数据库架构概览](#数据库架构概览)

---

## 🔧 前置要求

### 系统要求
- **MySQL**: 5.7 或更高版本 (推荐 8.0+)
- **PHP**: 7.2 或更高版本
- **Composer**: 最新版本
- **Laravel**: 5.8

### 权限要求
确保MySQL用户拥有以下权限:
- CREATE DATABASE
- CREATE TABLE
- ALTER TABLE
- DROP TABLE
- INSERT, UPDATE, DELETE, SELECT
- REFERENCES (用于外键)

---

## 🚀 数据库初始化方法

系统提供两种数据库初始化方法,您可以根据需要选择:

### 方法一: 使用 Laravel 迁移 (推荐用于开发环境)

这是Laravel官方推荐的方法,适合开发环境和版本控制。

#### 步骤 1: 配置数据库连接

编辑项目根目录下的 `.env` 文件:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dental_medical_management
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### 步骤 2: 创建数据库

登录MySQL并创建数据库:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE dental_medical_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### 步骤 3: 运行迁移

在项目根目录执行:

```bash
# 运行所有迁移文件,创建数据库表
php artisan migrate

# 如果需要重置数据库并重新迁移
php artisan migrate:fresh

# 运行迁移并填充种子数据
php artisan migrate:fresh --seed
```

#### 步骤 4: 填充基础数据 (可选)

```bash
# 运行所有种子数据
php artisan db:seed

# 或运行特定的种子类
php artisan db:seed --class=PatientsTableSeeder
```

---

### 方法二: 使用 SQL 脚本 (推荐用于生产环境)

此方法适合快速部署和生产环境初始化。

#### 步骤 1: 初始化数据库结构

使用提供的 SQL 初始化脚本创建所有数据库表:

```bash
# 方式 1: 通过命令行执行
mysql -u root -p < database/init_database.sql

# 方式 2: 登录MySQL后执行
mysql -u root -p
source /path/to/database/init_database.sql;
```

**注意**: 此脚本会:
- 自动创建名为 `dental_medical_management` 的数据库
- 删除已存在的同名表 (谨慎使用!)
- 创建所有53个数据表
- 设置所有外键约束

#### 步骤 2: 填充基础数据

运行种子数据脚本以创建必要的初始数据:

```bash
# 方式 1: 通过命令行执行
mysql -u root -p dental_medical_management < database/seed_initial_data.sql

# 方式 2: 登录MySQL后执行
mysql -u root -p dental_medical_management
source /path/to/database/seed_initial_data.sql;
```

#### 步骤 3: 配置 Laravel 环境

确保 `.env` 文件中的数据库配置正确:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dental_medical_management
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### 步骤 4: 生成应用密钥

```bash
php artisan key:generate
```

---

## 📚 初始化脚本说明

### 1. `init_database.sql` - 数据库结构初始化脚本

**文件位置**: `database/init_database.sql`

**功能说明**:
- 创建完整的数据库结构
- 包含53个数据表
- 设置94个外键约束
- 使用UTF8MB4字符集支持国际化

**数据表分类**:

| 类别 | 表数量 | 主要表 |
|------|--------|--------|
| 核心系统 | 5 | users, roles, branches, password_resets, failed_jobs |
| 患者管理 | 2 | patients, insurance_companies |
| 预约系统 | 4 | appointments, book_appointments, online_bookings, appointment_histories |
| 医疗服务 | 9 | medical_services, invoices, invoice_items, quotations, prescriptions |
| 医疗记录 | 7 | treatments, surgeries, chronic_diseases, allergies, dental_charts |
| 费用管理 | 6 | expenses, expense_categories, expense_items, expense_payments, suppliers |
| 人力资源 | 8 | employee_contracts, pay_slips, salary_allowances, leave_requests |
| 保险索赔 | 3 | claim_rates, doctor_claims, doctor_claim_payments |
| 会计系统 | 3 | accounting_equations, chart_of_account_categories, chart_of_account_items |
| 通信通知 | 5 | sms_loggings, notifications, billing_email_notifications |
| 审计队列 | 2 | audits, jobs |

**执行时间**: 约 2-5 秒 (取决于服务器性能)

**生成的数据库大小**: 约 50-100 MB (空表)

---

### 2. `seed_initial_data.sql` - 基础数据种子脚本

**文件位置**: `database/seed_initial_data.sql`

**功能说明**:
- 填充系统运行所需的基础数据
- 创建默认管理员和测试账户
- 设置基础配置和参考数据

**包含的数据**:

#### 🔐 用户和角色
- **7个系统角色**:
  - Super Admin (超级管理员)
  - Doctor (医生)
  - Nurse (护士)
  - Receptionist (前台)
  - Pharmacist (药剂师)
  - Accountant (会计)
  - Lab Technician (实验室技术员)

- **4个默认用户账户**:
  - 超级管理员
  - 默认医生
  - 默认护士
  - 默认前台

#### 🏥 组织结构
- 2个分支机构:
  - Main Branch (主分支)
  - Downtown Clinic (市中心诊所)

#### 💰 财务配置
- 10个费用分类 (水电、租金、工资等)
- 5个会计科目分类 (资产、负债、权益、收入、费用)
- 20个会计科目项目

#### 🦷 医疗服务
- 10种医疗服务项目:
  - 全科咨询 (50,000 UGX)
  - 洗牙 (75,000 UGX)
  - 拔牙 (100,000 UGX)
  - 补牙 (120,000 UGX)
  - 根管治疗 (300,000 UGX)
  - 牙冠 (500,000 UGX)
  - 牙齿美白 (250,000 UGX)
  - 牙科X光 (50,000 UGX)
  - 正畸咨询 (100,000 UGX)
  - 急诊治疗 (150,000 UGX)

#### 🏥 保险公司
- 4家保险公司及其索赔率配置 (默认80%)

#### 📅 其他配置
- 6种休假类型
- 5个公共假期 (乌干达)

**执行时间**: 约 1-3 秒

---

## 🔑 默认账户信息

### 重要提示
> ⚠️ **安全警告**: 首次登录后请立即修改所有默认密码!

### 默认登录凭证

| 角色 | 邮箱 | 密码 | 用途 |
|------|------|------|------|
| 超级管理员 | admin@dentalmedical.com | password | 系统管理、配置 |
| 医生 | doctor@dentalmedical.com | password | 医疗服务、诊断 |
| 护士 | nurse@dentalmedical.com | password | 护理服务 |
| 前台 | receptionist@dentalmedical.com | password | 预约、接待 |

### 密码哈希说明
默认密码 `password` 已使用 bcrypt 算法加密:
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

这是Laravel默认的测试密码哈希。

---

## 🔍 故障排除

### 问题 1: "Access denied for user"

**原因**: MySQL用户权限不足

**解决方案**:
```sql
-- 授予用户所有权限
GRANT ALL PRIVILEGES ON dental_medical_management.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

---

### 问题 2: "Unknown database 'dental_medical_management'"

**原因**: 数据库未创建

**解决方案**:
```sql
CREATE DATABASE dental_medical_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

### 问题 3: "Foreign key constraint fails"

**原因**: 外键约束冲突,通常是因为插入顺序错误

**解决方案**:
```sql
-- 暂时禁用外键检查
SET FOREIGN_KEY_CHECKS = 0;

-- 执行你的SQL操作
SOURCE /path/to/your/script.sql;

-- 重新启用外键检查
SET FOREIGN_KEY_CHECKS = 1;
```

---

### 问题 4: "Syntax error near..."

**原因**: SQL语法错误或MySQL版本不兼容

**解决方案**:
1. 确保使用 MySQL 5.7+
2. 检查 SQL 文件编码是否为 UTF-8
3. 使用正确的命令执行脚本

---

### 问题 5: Laravel 迁移失败

**原因**: 迁移文件顺序或依赖问题

**解决方案**:
```bash
# 重置所有迁移
php artisan migrate:reset

# 清除缓存
php artisan cache:clear
php artisan config:clear

# 重新运行迁移
php artisan migrate:fresh
```

---

### 问题 6: "SQLSTATE[42000]: Syntax error or access violation: 1071"

**原因**: 索引键太长 (通常在旧版MySQL中)

**解决方案**:

编辑 `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Schema;

public function boot()
{
    Schema::defaultStringLength(191);
}
```

---

## 📊 数据库架构概览

### 核心关系图

```
users (用户)
  ├── roles (角色)
  ├── branches (分支)
  └── employee_contracts (员工合同)

patients (患者)
  ├── insurance_companies (保险公司)
  ├── appointments (预约)
  │   ├── treatments (治疗)
  │   ├── prescriptions (处方)
  │   └── invoices (发票)
  │       ├── invoice_items (发票项目)
  │       └── invoice_payments (发票付款)
  ├── chronic_diseases (慢性病)
  ├── allergies (过敏)
  └── dental_charts (牙科图表)

expenses (费用)
  ├── expense_categories (费用分类)
  ├── expense_items (费用项目)
  ├── expense_payments (费用付款)
  └── suppliers (供应商)
```

### 数据表统计

- **总表数**: 53 个
- **外键约束**: 94 个
- **索引数**: 120+ 个
- **枚举类型**: 30+ 个

### 软删除支持

所有主要业务表都支持软删除 (使用 `deleted_at` 字段):
- users
- patients
- appointments
- invoices
- expenses
- 等等...

这意味着删除操作不会真正删除数据,只是标记为已删除。

---

## 🔄 数据库维护

### 备份数据库

```bash
# 完整备份
mysqldump -u root -p dental_medical_management > backup_$(date +%Y%m%d).sql

# 仅备份结构
mysqldump -u root -p --no-data dental_medical_management > schema_$(date +%Y%m%d).sql

# 仅备份数据
mysqldump -u root -p --no-create-info dental_medical_management > data_$(date +%Y%m%d).sql
```

### 恢复数据库

```bash
mysql -u root -p dental_medical_management < backup_20240101.sql
```

### 优化数据库

```sql
-- 优化所有表
OPTIMIZE TABLE users, patients, appointments, invoices;

-- 分析表
ANALYZE TABLE users, patients, appointments;

-- 检查表
CHECK TABLE users, patients, appointments;
```

---

## 📝 数据库迁移版本管理

如果使用 Laravel 迁移系统,系统会在 `migrations` 表中记录所有执行过的迁移:

```sql
-- 查看已执行的迁移
SELECT * FROM migrations ORDER BY batch DESC;

-- 查看迁移状态
-- 在命令行执行:
php artisan migrate:status
```

---

## 🎯 下一步操作

数据库初始化完成后,您可以:

1. **配置应用**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

2. **安装依赖**:
   ```bash
   composer install
   npm install
   npm run dev
   ```

3. **启动服务器**:
   ```bash
   php artisan serve
   ```

4. **访问系统**:
   - 访问: http://localhost:8000
   - 登录: admin@dentalmedical.com / password

5. **修改默认密码**: 立即修改所有默认账户的密码

---

## 📞 获取帮助

如果遇到问题:

1. 查看 Laravel 日志: `storage/logs/laravel.log`
2. 检查 MySQL 错误日志
3. 参考 Laravel 官方文档: https://laravel.com/docs/5.8
4. 查看项目 README.md

---

## ⚖️ 许可证

本数据库脚本遵循项目主许可证。

---

**最后更新**: 2024
**版本**: 1.0
**维护者**: Dental Medical Management System Team
