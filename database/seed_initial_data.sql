-- ============================================================================
-- 牙科医疗管理系统 - 基础数据种子脚本
-- Dental Medical Management System - Initial Seed Data
-- ============================================================================
-- 此脚本包含系统启动所需的基础数据（中国地区版本）
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
(1, '超级管理员', NOW(), NOW(), NULL),
(2, '医生', NOW(), NOW(), NULL),
(3, '护士', NOW(), NOW(), NULL),
(4, '前台接待', NOW(), NOW(), NULL),
(5, '药剂师', NOW(), NOW(), NULL),
(6, '会计', NOW(), NOW(), NULL),
(7, '化验师', NOW(), NOW(), NULL);

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
-- 密码: password (已使用bcrypt加密，请在首次登录后立即修改)
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
    '张',
    '管理员',
    'admin@dental.com',
    '13800138000',
    NULL,
    NULL,
    NULL,
    NOW(),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    NOW(),
    1, -- 超级管理员
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
    '李',
    '医生',
    'doctor@dental.com',
    '13800138001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    2, -- 医生
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
    '王',
    '护士',
    'nurse@dental.com',
    '13800138002',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    3, -- 护士
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
    '赵',
    '前台',
    'reception@dental.com',
    '13800138003',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    4, -- 前台接待
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
(1, '总院', 'true', 1, NOW(), NOW(), NULL),
(2, '分院一部', 'true', 1, NOW(), NOW(), NULL),
(3, '分院二部', 'true', 1, NOW(), NOW(), NULL);

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
(1, '年假', NOW(), NOW(), NULL),
(2, '病假', NOW(), NOW(), NULL),
(3, '产假', NOW(), NOW(), NULL),
(4, '陪产假', NOW(), NOW(), NULL),
(5, '事假', NOW(), NOW(), NULL),
(6, '调休', NOW(), NOW(), NULL),
(7, '婚假', NOW(), NOW(), NULL),
(8, '丧假', NOW(), NOW(), NULL);

-- ============================================================================
-- 6. 默认费用分类 (Expense Categories)
-- ============================================================================
TRUNCATE TABLE expense_categories;

INSERT INTO expense_categories (id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(1, '水电费', 1, NOW(), NOW(), NULL),
(2, '房租', 1, NOW(), NOW(), NULL),
(3, '工资薪酬', 1, NOW(), NOW(), NULL),
(4, '医疗耗材', 1, NOW(), NOW(), NULL),
(5, '设备维护', 1, NOW(), NOW(), NULL),
(6, '市场营销', 1, NOW(), NOW(), NULL),
(7, '保险费用', 1, NOW(), NOW(), NULL),
(8, '交通费', 1, NOW(), NOW(), NULL),
(9, '通讯费', 1, NOW(), NOW(), NULL),
(10, '办公用品', 1, NOW(), NOW(), NULL),
(11, '培训费用', 1, NOW(), NOW(), NULL),
(12, '其他费用', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 7. 默认会计科目分类 (Chart of Account Categories)
-- ============================================================================
TRUNCATE TABLE chart_of_account_categories;

INSERT INTO chart_of_account_categories (id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(1, '资产', 1, NOW(), NOW(), NULL),
(2, '负债', 1, NOW(), NOW(), NULL),
(3, '所有者权益', 1, NOW(), NOW(), NULL),
(4, '收入', 1, NOW(), NOW(), NULL),
(5, '费用', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 8. 默认会计科目项目 (Chart of Account Items)
-- ============================================================================
TRUNCATE TABLE chart_of_account_items;

-- 资产类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(1, '库存现金', 1, NOW(), NOW(), NULL),
(1, '银行存款', 1, NOW(), NOW(), NULL),
(1, '应收账款', 1, NOW(), NOW(), NULL),
(1, '医疗设备', 1, NOW(), NOW(), NULL),
(1, '办公设备', 1, NOW(), NOW(), NULL),
(1, '医疗耗材库存', 1, NOW(), NOW(), NULL),
(1, '固定资产', 1, NOW(), NOW(), NULL),
(1, '无形资产', 1, NOW(), NOW(), NULL);

-- 负债类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(2, '应付账款', 1, NOW(), NOW(), NULL),
(2, '应付工资', 1, NOW(), NOW(), NULL),
(2, '应交税费', 1, NOW(), NOW(), NULL),
(2, '短期借款', 1, NOW(), NOW(), NULL),
(2, '长期借款', 1, NOW(), NOW(), NULL);

-- 所有者权益类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(3, '实收资本', 1, NOW(), NOW(), NULL),
(3, '资本公积', 1, NOW(), NOW(), NULL),
(3, '盈余公积', 1, NOW(), NOW(), NULL),
(3, '未分配利润', 1, NOW(), NOW(), NULL);

-- 收入类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(4, '诊疗收入', 1, NOW(), NOW(), NULL),
(4, '医保收入', 1, NOW(), NOW(), NULL),
(4, '商保收入', 1, NOW(), NOW(), NULL),
(4, '其他业务收入', 1, NOW(), NOW(), NULL);

-- 费用类账户
INSERT INTO chart_of_account_items (chart_of_account_category_id, name, _who_added, created_at, updated_at, deleted_at) VALUES
(5, '工资薪金', 1, NOW(), NOW(), NULL),
(5, '社保公积金', 1, NOW(), NOW(), NULL),
(5, '房租费用', 1, NOW(), NOW(), NULL),
(5, '水电费用', 1, NOW(), NOW(), NULL),
(5, '医疗耗材费用', 1, NOW(), NOW(), NULL),
(5, '设备折旧', 1, NOW(), NOW(), NULL),
(5, '管理费用', 1, NOW(), NOW(), NULL),
(5, '销售费用', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 9. 默认医疗服务项目 (Medical Services)
-- ============================================================================
TRUNCATE TABLE medical_services;

INSERT INTO medical_services (id, name, price, _who_added, created_at, updated_at, deleted_at) VALUES
(1, '口腔检查', 50.00, 1, NOW(), NOW(), NULL),
(2, '洁牙（洗牙）', 150.00, 1, NOW(), NOW(), NULL),
(3, '拔牙', 200.00, 1, NOW(), NOW(), NULL),
(4, '补牙（树脂充填）', 300.00, 1, NOW(), NOW(), NULL),
(5, '根管治疗', 800.00, 1, NOW(), NOW(), NULL),
(6, '烤瓷牙冠', 1500.00, 1, NOW(), NOW(), NULL),
(7, '全瓷牙冠', 2500.00, 1, NOW(), NOW(), NULL),
(8, '牙齿美白', 800.00, 1, NOW(), NOW(), NULL),
(9, '口腔X光片', 80.00, 1, NOW(), NOW(), NULL),
(10, '口腔CT', 300.00, 1, NOW(), NOW(), NULL),
(11, '牙齿矫正咨询', 100.00, 1, NOW(), NOW(), NULL),
(12, '种植牙', 8000.00, 1, NOW(), NOW(), NULL),
(13, '牙周治疗', 500.00, 1, NOW(), NOW(), NULL),
(14, '儿童涂氟', 80.00, 1, NOW(), NOW(), NULL),
(15, '窝沟封闭', 120.00, 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 10. 示例保险公司 (Insurance Companies)
-- ============================================================================
TRUNCATE TABLE insurance_companies;

INSERT INTO insurance_companies (id, name, email, phone_no, _who_added, created_at, updated_at, deleted_at) VALUES
(1, '中国人寿保险', 'service@chinalife.com.cn', '95519', 1, NOW(), NOW(), NULL),
(2, '中国平安保险', 'service@pingan.com', '95511', 1, NOW(), NOW(), NULL),
(3, '中国太平洋保险', 'service@cpic.com.cn', '95500', 1, NOW(), NOW(), NULL),
(4, '中国人民保险', 'service@picc.com.cn', '95518', 1, NOW(), NOW(), NULL),
(5, '泰康人寿', 'service@taikang.com', '95522', 1, NOW(), NOW(), NULL),
(6, '新华人寿', 'service@newchinalife.com', '95567', 1, NOW(), NOW(), NULL);

-- ============================================================================
-- 11. 示例索赔率 (Claim Rates)
-- ============================================================================
TRUNCATE TABLE claim_rates;

-- 为每个保险公司和医疗服务设置默认索赔率
-- 医保类项目索赔率较高(70%)，商保类索赔率(50-80%)
INSERT INTO claim_rates (insurance_company_id, medical_service_id, rate, _who_added, created_at, updated_at, deleted_at)
SELECT ic.id, ms.id,
    CASE
        WHEN ms.id IN (1, 2, 3, 4, 9, 13, 14, 15) THEN 70.00  -- 基础诊疗项目
        WHEN ms.id IN (5, 6, 8, 11) THEN 60.00                -- 中等诊疗项目
        ELSE 50.00                                             -- 高端诊疗项目
    END as rate,
    1, NOW(), NOW(), NULL
FROM insurance_companies ic
CROSS JOIN medical_services ms
WHERE ic.id <= 6 AND ms.id <= 15;

-- ============================================================================
-- 12. 默认假期 (Holidays)
-- ============================================================================
TRUNCATE TABLE holidays;

-- 插入中国法定节假日
INSERT INTO holidays (id, name, holiday_date, created_at, updated_at, deleted_at) VALUES
(1, '元旦', CONCAT(YEAR(NOW()), '-01-01'), NOW(), NOW(), NULL),
(2, '春节', CONCAT(YEAR(NOW()), '-02-10'), NOW(), NOW(), NULL),
(3, '春节', CONCAT(YEAR(NOW()), '-02-11'), NOW(), NOW(), NULL),
(4, '春节', CONCAT(YEAR(NOW()), '-02-12'), NOW(), NOW(), NULL),
(5, '清明节', CONCAT(YEAR(NOW()), '-04-04'), NOW(), NOW(), NULL),
(6, '劳动节', CONCAT(YEAR(NOW()), '-05-01'), NOW(), NOW(), NULL),
(7, '劳动节', CONCAT(YEAR(NOW()), '-05-02'), NOW(), NOW(), NULL),
(8, '劳动节', CONCAT(YEAR(NOW()), '-05-03'), NOW(), NOW(), NULL),
(9, '端午节', CONCAT(YEAR(NOW()), '-06-10'), NOW(), NOW(), NULL),
(10, '中秋节', CONCAT(YEAR(NOW()), '-09-17'), NOW(), NOW(), NULL),
(11, '国庆节', CONCAT(YEAR(NOW()), '-10-01'), NOW(), NOW(), NULL),
(12, '国庆节', CONCAT(YEAR(NOW()), '-10-02'), NOW(), NOW(), NULL),
(13, '国庆节', CONCAT(YEAR(NOW()), '-10-03'), NOW(), NOW(), NULL);

-- ============================================================================
-- 13. 会计方程式 (Accounting Equations)
-- ============================================================================
TRUNCATE TABLE accounting_equations;

-- 插入基本会计方程式规则
INSERT INTO accounting_equations (id, name, equation, created_at, updated_at, deleted_at) VALUES
(1, '资产', '负债 + 所有者权益', NOW(), NOW(), NULL),
(2, '所有者权益', '资产 - 负债', NOW(), NOW(), NULL),
(3, '负债', '资产 - 所有者权益', NOW(), NOW(), NULL),
(4, '利润', '收入 - 费用', NOW(), NOW(), NULL);

-- ============================================================================
-- 完成
-- ============================================================================
-- 启用外键检查
SET FOREIGN_KEY_CHECKS = 1;

-- 输出完成信息
SELECT '=============================================' AS '';
SELECT '基础数据种子脚本执行完成！' AS '状态';
SELECT '=============================================' AS '';
SELECT '已创建的数据:' AS '';
SELECT '- 7个用户角色（超级管理员、医生、护士等）' AS '';
SELECT '- 4个默认用户账户（密码: password）' AS '';
SELECT '- 3个分支机构（总院、分院一部、分院二部）' AS '';
SELECT '- 8种休假类型（年假、病假、产假等）' AS '';
SELECT '- 12个费用分类（水电费、房租、工资等）' AS '';
SELECT '- 5个会计科目分类及相关项目' AS '';
SELECT '- 15种医疗服务（价格单位：人民币元）' AS '';
SELECT '- 6家保险公司及相关索赔率' AS '';
SELECT '- 13个法定节假日' AS '';
SELECT '' AS '';
SELECT '默认登录凭证:' AS '';
SELECT '管理员: admin@dental.com / password' AS '';
SELECT '医生: doctor@dental.com / password' AS '';
SELECT '护士: nurse@dental.com / password' AS '';
SELECT '前台: reception@dental.com / password' AS '';
SELECT '' AS '';
SELECT '重要提示: 请在首次登录后立即修改默认密码！' AS '';
SELECT '=============================================' AS '';
