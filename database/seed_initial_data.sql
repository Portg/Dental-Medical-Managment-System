-- ============================================================================
-- 牙科医疗管理系统 - 基础数据种子脚本
-- Dental Medical Management System - Initial Seed Data
-- ============================================================================
-- 此脚本包含系统启动所需的基础数据
-- 包括: 默认角色、默认管理员账户、默认分支机构等
-- ============================================================================

USE dental_medical_management;

-- 禁用外键检查
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. 角色数据 (Roles)
-- ============================================================================
-- 清空现有数据
TRUNCATE TABLE roles;

-- 插入基础角色
INSERT INTO roles (id, name, created_at, updated_at, deleted_at) VALUES
(1, 'Super Admin', NOW(), NOW(), NULL),
(2, 'Doctor', NOW(), NOW(), NULL),
(3, 'Nurse', NOW(), NOW(), NULL),
(4, 'Receptionist', NOW(), NOW(), NULL),
(5, 'Pharmacist', NOW(), NOW(), NULL),
(6, 'Accountant', NOW(), NOW(), NULL),
(7, 'Lab Technician', NOW(), NOW(), NULL);

-- ============================================================================
-- 2. 默认分支机构 (Branches)
-- ============================================================================
-- 注意: 需要先创建管理员用户,因为branches表有_who_added外键
-- 暂时跳过,在用户创建后再添加

-- ============================================================================
-- 3. 默认管理员账户 (Users)
-- ============================================================================
-- 清空现有数据
TRUNCATE TABLE users;

-- 插入默认超级管理员
-- 密码: admin123 (已使用bcrypt加密，请在首次登录后立即修改)
-- 注意: 以下密码哈希对应 'admin123'
INSERT INTO users (
    id,
    surname,
    othername,
    email,
    phone_no,
    alternative_no,
    photo,
    nin,
    email_verified_at,
    password,
    last_seen,
    role_id,
    branch_id,
    remember_token,
    created_at,
    updated_at,
    deleted_at
) VALUES (
    1,
    'Administrator',
    'System',
    'admin@dentalmedical.com',
    '+256700000000',
    NULL,
    NULL,
    NULL,
    NOW(),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    NOW(),
    1, -- Super Admin
    NULL, -- 分支将在后续添加
    NULL,
    NOW(),
    NOW(),
    NULL
);

-- 插入默认医生账户
INSERT INTO users (
    id,
    surname,
    othername,
    email,
    phone_no,
    password,
    role_id,
    email_verified_at,
    created_at,
    updated_at,
    deleted_at
) VALUES (
    2,
    'Doctor',
    'Default',
    'doctor@dentalmedical.com',
    '+256700000001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    2, -- Doctor
    NOW(),
    NOW(),
    NOW(),
    NULL
);

-- 插入默认护士账户
INSERT INTO users (
    id,
    surname,
    othername,
    email,
    phone_no,
    password,
    role_id,
    email_verified_at,
    created_at,
    updated_at,
    deleted_at
) VALUES (
    3,
    'Nurse',
    'Default',
    'nurse@dentalmedical.com',
    '+256700000002',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    3, -- Nurse
    NOW(),
    NOW(),
    NOW(),
    NULL
);

-- 插入默认前台接待员账户
INSERT INTO users (
    id,
    surname,
    othername,
    email,
    phone_no,
    password,
    role_id,
    email_verified_at,
    created_at,
    updated_at,
    deleted_at
) VALUES (
    4,
    'Receptionist',
    'Default',
    'receptionist@dentalmedical.com',
    '+256700000003',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    4, -- Receptionist
    NOW(),
    NOW(),
    NOW(),
    NULL
);

-- ============================================================================
-- 4. 默认分支机构 (Branches)
-- ============================================================================
-- 现在可以插入分支数据了
TRUNCATE TABLE branches;

INSERT INTO branches (id, name, is_active, _who_added, created_at, updated_at, deleted_at) VALUES
(1, 'Main Branch', 'true', 1, NOW(), NOW(), NULL),
(2, 'Downtown Clinic', 'true', 1, NOW(), NOW(), NULL);

-- 更新管理员的分支信息
UPDATE users SET branch_id = 1 WHERE id = 1;
UPDATE users SET branch_id = 1 WHERE id = 2;
UPDATE users SET branch_id = 1 WHERE id = 3;
UPDATE users SET branch_id = 1 WHERE id = 4;

-- ============================================================================
-- 5. 默认休假类型 (Leave Types)
-- ============================================================================
TRUNCATE TABLE leave_types;

INSERT INTO leave_types (id, name, created_at, updated_at, deleted_at) VALUES
(1, 'Annual Leave', NOW(), NOW(), NULL),
(2, 'Sick Leave', NOW(), NOW(), NULL),
(3, 'Maternity Leave', NOW(), NOW(), NULL),
(4, 'Paternity Leave', NOW(), NOW(), NULL),
(5, 'Compassionate Leave', NOW(), NOW(), NULL),
(6, 'Study Leave', NOW(), NOW(), NULL);

-- ============================================================================
-- 6. 默认费用分类 (Expense Categories)
-- ============================================================================
TRUNCATE TABLE expense_categories;

INSERT INTO expense_categories (id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(1, 'Utilities', 1, NOW(), NOW(), NULL),
(2, 'Rent', 1, NOW(), NOW(), NULL),
(3, 'Salaries', 1, NOW(), NOW(), NULL),
(4, 'Medical Supplies', 1, NOW(), NOW(), NULL),
(5, 'Equipment Maintenance', 1, NOW(), NOW(), NULL),
(6, 'Marketing', 1, NOW(), NOW(), NULL),
(7, 'Insurance', 1, NOW(), NOW(), NULL),
(8, 'Transportation', 1, NOW(), NOW(), NULL),
(9, 'Communication', 1, NOW(), NOW(), NULL),
(10, 'Miscellaneous', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 7. 默认会计科目分类 (Chart of Account Categories)
-- ============================================================================
TRUNCATE TABLE chart_of_account_categories;

INSERT INTO chart_of_account_categories (id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(1, 'Assets', 1, NOW(), NOW(), NULL),
(2, 'Liabilities', 1, NOW(), NOW(), NULL),
(3, 'Equity', 1, NOW(), NOW(), NULL),
(4, 'Revenue', 1, NOW(), NOW(), NULL),
(5, 'Expenses', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 8. 默认会计科目项目 (Chart of Account Items)
-- ============================================================================
TRUNCATE TABLE chart_of_account_items;

-- 资产类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(1, 'Cash', 1, NOW(), NOW(), NULL),
(1, 'Bank Account', 1, NOW(), NOW(), NULL),
(1, 'Accounts Receivable', 1, NOW(), NOW(), NULL),
(1, 'Medical Equipment', 1, NOW(), NOW(), NULL),
(1, 'Office Equipment', 1, NOW(), NOW(), NULL),
(1, 'Inventory - Medical Supplies', 1, NOW(), NOW(), NULL);

-- 负债类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(2, 'Accounts Payable', 1, NOW(), NOW(), NULL),
(2, 'Loans Payable', 1, NOW(), NOW(), NULL),
(2, 'Accrued Expenses', 1, NOW(), NOW(), NULL);

-- 权益类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(3, 'Owner\'s Equity', 1, NOW(), NOW(), NULL),
(3, 'Retained Earnings', 1, NOW(), NOW(), NULL);

-- 收入类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(4, 'Patient Service Revenue', 1, NOW(), NOW(), NULL),
(4, 'Insurance Claims Revenue', 1, NOW(), NOW(), NULL),
(4, 'Other Income', 1, NOW(), NOW(), NULL);

-- 费用类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(5, 'Salaries and Wages', 1, NOW(), NOW(), NULL),
(5, 'Rent Expense', 1, NOW(), NOW(), NULL),
(5, 'Utilities Expense', 1, NOW(), NOW(), NULL),
(5, 'Medical Supplies Expense', 1, NOW(), NOW(), NULL),
(5, 'Equipment Maintenance', 1, NOW(), NOW(), NULL),
(5, 'Depreciation Expense', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 9. 默认医疗服务项目 (Medical Services)
-- ============================================================================
TRUNCATE TABLE medical_services;

INSERT INTO medical_services (id, name, price, _who_added, created_at, updated_at, deleted_at) VALUES
(1, 'General Consultation', 50000.00, 1, NOW(), NOW(), NULL),
(2, 'Dental Cleaning', 75000.00, 1, NOW(), NOW(), NULL),
(3, 'Tooth Extraction', 100000.00, 1, NOW(), NOW(), NULL),
(4, 'Dental Filling', 120000.00, 1, NOW(), NOW(), NULL),
(5, 'Root Canal Treatment', 300000.00, 1, NOW(), NOW(), NULL),
(6, 'Dental Crown', 500000.00, 1, NOW(), NOW(), NULL),
(7, 'Teeth Whitening', 250000.00, 1, NOW(), NOW(), NULL),
(8, 'Dental X-Ray', 50000.00, 1, NOW(), NOW(), NULL),
(9, 'Orthodontic Consultation', 100000.00, 1, NOW(), NOW(), NULL),
(10, 'Emergency Treatment', 150000.00, 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 10. 示例保险公司 (Insurance Companies)
-- ============================================================================
TRUNCATE TABLE insurance_companies;

INSERT INTO insurance_companies (id, name, email, phone_no, _who_added, created_at, updated_at, deleted_at) VALUES
(1, 'National Health Insurance', 'info@nhi.co.ug', '+256414000000', 1, NOW(), NOW(), NULL),
(2, 'AAR Healthcare', 'info@aar.co.ug', '+256414000001', 1, NOW(), NOW(), NULL),
(3, 'Jubilee Health Insurance', 'info@jubilee.co.ug', '+256414000002', 1, NOW(), NOW(), NULL),
(4, 'UAP Insurance', 'info@uap.co.ug', '+256414000003', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 11. 示例索赔率 (Claim Rates)
-- ============================================================================
TRUNCATE TABLE claim_rates;

-- 为每个保险公司和医疗服务设置默认索赔率 (80%)
INSERT INTO claim_rates (insurance_company_id, medical_service_id, rate, _who_added, created_at, updated_at, deleted_at)
SELECT ic.id, ms.id, 80.00, 1, NOW(), NOW(), NULL
FROM insurance_companies ic
CROSS JOIN medical_services ms
WHERE ic.id <= 4 AND ms.id <= 10;

-- ============================================================================
-- 12. 默认假期 (Holidays)
-- ============================================================================
TRUNCATE TABLE holidays;

-- 插入乌干达主要公共假期（示例）
INSERT INTO holidays (id, name, holiday_date, created_at, updated_at, deleted_at) VALUES
(1, 'New Year\'s Day', CONCAT(YEAR(NOW()), '-01-01'), NOW(), NOW(), NULL),
(2, 'Labour Day', CONCAT(YEAR(NOW()), '-05-01'), NOW(), NOW(), NULL),
(3, 'Independence Day', CONCAT(YEAR(NOW()), '-10-09'), NOW(), NOW(), NULL),
(4, 'Christmas Day', CONCAT(YEAR(NOW()), '-12-25'), NOW(), NOW(), NULL),
(5, 'Boxing Day', CONCAT(YEAR(NOW()), '-12-26'), NOW(), NOW(), NULL);

-- ============================================================================
-- 13. 会计方程式 (Accounting Equations)
-- ============================================================================
TRUNCATE TABLE accounting_equations;

-- 插入基本会计方程式规则
INSERT INTO accounting_equations (id, name, equation, created_at, updated_at, deleted_at) VALUES
(1, 'Assets', 'Liabilities + Equity', NOW(), NOW(), NULL),
(2, 'Equity', 'Assets - Liabilities', NOW(), NOW(), NULL),
(3, 'Liabilities', 'Assets - Equity', NOW(), NOW(), NULL);

-- ============================================================================
-- 完成
-- ============================================================================
-- 启用外键检查
SET FOREIGN_KEY_CHECKS = 1;

-- 输出完成信息
SELECT '=============================================' AS '';
SELECT '基础数据种子脚本执行完成！' AS 'Status';
SELECT '=============================================' AS '';
SELECT '已创建的数据:' AS '';
SELECT '- 7个用户角色' AS '';
SELECT '- 4个默认用户账户 (密码: password)' AS '';
SELECT '- 2个分支机构' AS '';
SELECT '- 6种休假类型' AS '';
SELECT '- 10个费用分类' AS '';
SELECT '- 5个会计科目分类及相关项目' AS '';
SELECT '- 10种医疗服务' AS '';
SELECT '- 4家保险公司及相关索赔率' AS '';
SELECT '- 5个公共假期' AS '';
SELECT '' AS '';
SELECT '默认登录凭证:' AS '';
SELECT '管理员: admin@dentalmedical.com / password' AS '';
SELECT '医生: doctor@dentalmedical.com / password' AS '';
SELECT '护士: nurse@dentalmedical.com / password' AS '';
SELECT '前台: receptionist@dentalmedical.com / password' AS '';
SELECT '' AS '';
SELECT '重要提示: 请在首次登录后立即修改默认密码！' AS '';
SELECT '=============================================' AS '';
