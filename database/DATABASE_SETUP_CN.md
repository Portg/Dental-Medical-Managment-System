# 数据库初始化指南 - 中国地区版本

本文档是 `DATABASE_SETUP.md` 的补充文档，专门针对中国地区的部署说明。

---

## 🇨🇳 中国地区特别说明

### 1. Laravel Seeder 方式（推荐）

所有 Seeder 文件已针对中国地区进行了本地化调整。

#### 快速开始

```bash
# 1. 配置数据库（.env 文件）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dental_medical_management
DB_USERNAME=root
DB_PASSWORD=your_password

# 2. 创建数据库
mysql -u root -p -e "CREATE DATABASE dental_medical_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. 运行迁移和种子（一步完成）
php artisan migrate:fresh --seed
```

#### 包含的中国化数据

执行 `php artisan db:seed` 后将创建：

**用户和角色**（中文名称）
- 7个角色：超级管理员、医生、护士、前台接待、药剂师、会计、化验师
- 4个测试账户：张管理员、李医生、王护士、赵前台

**分支机构**（中文名称）
- 总院
- 分院一部
- 分院二部

**休假类型**（符合中国劳动法）
- 年假、病假、产假、陪产假、事假、调休、婚假、丧假

**费用分类**（中文）
- 水电费、房租、工资薪酬、医疗耗材、设备维护、市场营销、保险费用、交通费、通讯费、办公用品、培训费用、其他费用

**会计科目**（符合中国会计准则）
- 资产、负债、所有者权益、收入、费用
- 包含库存现金、银行存款、应收账款、应付账款、应交税费等

**医疗服务**（人民币定价）
```
口腔检查        ¥50
洁牙（洗牙）    ¥150
拔牙            ¥200
补牙（树脂充填）¥300
根管治疗        ¥800
烤瓷牙冠        ¥1,500
全瓷牙冠        ¥2,500
牙齿美白        ¥800
口腔X光片       ¥80
口腔CT          ¥300
牙齿矫正咨询    ¥100
种植牙          ¥8,000
牙周治疗        ¥500
儿童涂氟        ¥80
窝沟封闭        ¥120
```

**保险公司**（中国主要保险公司）
- 中国人寿保险（95519）
- 中国平安保险（95511）
- 中国太平洋保险（95500）
- 中国人民保险（95518）
- 泰康人寿（95522）
- 新华人寿（95567）

**索赔率**（分级配置）
- 基础诊疗项目：70%
- 中等诊疗项目：60%
- 高端诊疗项目：50%

**法定节假日**（中国）
- 元旦、春节、清明节、劳动节、端午节、中秋节、国庆节

---

### 2. SQL 脚本方式

如果使用 SQL 脚本方式，执行：

```bash
# 初始化数据库结构
mysql -u root -p < database/init_database.sql

# 填充中国化基础数据
mysql -u root -p dental_medical_management < database/seed_initial_data.sql
```

`seed_initial_data.sql` 已更新为中文版本，包含所有上述中国化数据。

---

## 📝 Laravel Seeder 详细说明

### 可用的 Seeder 类

所有 Seeder 文件位于 `database/seeds/` 目录：

| Seeder 文件 | 说明 | 数据量 |
|------------|------|--------|
| DatabaseSeeder.php | 主种子，调用所有子种子 | - |
| RolesTableSeeder.php | 系统角色（中文） | 7条 |
| UsersTableSeeder.php | 默认用户（中文姓名） | 4条 |
| BranchesTableSeeder.php | 分支机构（中文名称） | 3条 |
| LeaveTypesTableSeeder.php | 休假类型（中文） | 8条 |
| ExpenseCategoriesTableSeeder.php | 费用分类（中文） | 12条 |
| ChartOfAccountsTableSeeder.php | 会计科目（符合中国会计准则） | 5类29项 |
| MedicalServicesTableSeeder.php | 医疗服务（人民币定价） | 15条 |
| InsuranceCompaniesTableSeeder.php | 保险公司（中国公司） | 6条 |
| ClaimRatesTableSeeder.php | 索赔率（分级配置） | 90条 |
| HolidaysTableSeeder.php | 法定节假日（中国） | 13条 |
| AccountingEquationsTableSeeder.php | 会计方程式（中文） | 4条 |

### 使用方法

```bash
# 运行所有种子
php artisan db:seed

# 运行特定种子
php artisan db:seed --class=RolesTableSeeder
php artisan db:seed --class=MedicalServicesTableSeeder

# 重置并运行所有种子
php artisan migrate:fresh --seed

# 仅重置种子数据（保留表结构）
php artisan db:seed  # 会先truncate再插入
```

### 查看执行效果

```bash
# 进入 tinker 查看数据
php artisan tinker

# 查看角色
>>> DB::table('roles')->get();

# 查看用户
>>> DB::table('users')->select('surname', 'othername', 'email')->get();

# 查看医疗服务
>>> DB::table('medical_services')->select('name', 'price')->get();

# 查看保险公司
>>> DB::table('insurance_companies')->select('name', 'phone_no')->get();
```

---

## 🔑 默认登录凭证（中国化）

| 角色 | 姓名 | 邮箱 | 密码 | 手机号 |
|------|------|------|------|--------|
| 超级管理员 | 张管理员 | admin@dental.com | password | 13800138000 |
| 医生 | 李医生 | doctor@dental.com | password | 13800138001 |
| 护士 | 王护士 | nurse@dental.com | password | 13800138002 |
| 前台 | 赵前台 | reception@dental.com | password | 13800138003 |

**⚠️ 重要提示**：首次登录后请立即修改默认密码！

---

## 📊 数据对比：国际版 vs 中国版

| 项目 | 国际版 | 中国版 |
|------|--------|--------|
| 角色名称 | Super Admin, Doctor... | 超级管理员、医生... |
| 用户姓名 | Administrator, Default Doctor | 张管理员、李医生 |
| 邮箱域名 | dentalmedical.com | dental.com |
| 手机号格式 | +256700000000 | 13800138000 |
| 分支名称 | Main Branch, Downtown Clinic | 总院、分院一部 |
| 休假类型 | Annual Leave, Sick Leave | 年假、病假、产假 |
| 费用分类 | Utilities, Rent | 水电费、房租、工资薪酬 |
| 会计科目 | Assets, Liabilities | 资产、负债、所有者权益 |
| 医疗服务 | General Consultation | 口腔检查、洁牙 |
| 货币单位 | UGX（乌干达先令） | CNY（人民币元） |
| 价格范围 | 50,000-500,000 | 50-8,000 |
| 保险公司 | National Health Insurance | 中国人寿保险 |
| 假期 | Uganda holidays | 中国法定节假日 |

---

## 🔧 自定义配置

### 调整医疗服务价格

编辑 `database/seeds/MedicalServicesTableSeeder.php`：

```php
$services = [
    ['name' => '口腔检查', 'price' => 80.00],  // 从50元改为80元
    ['name' => '洁牙（洗牙）', 'price' => 200.00],  // 从150元改为200元
    // ...
];
```

然后重新运行：
```bash
php artisan db:seed --class=MedicalServicesTableSeeder
```

### 添加更多保险公司

编辑 `database/seeds/InsuranceCompaniesTableSeeder.php`：

```php
$companies = [
    // ... 现有公司
    [
        'name' => '您的保险公司',
        'email' => 'service@yourinsurance.com',
        'phone_no' => '95XXX',
        '_who_added' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
];
```

### 更新节假日

编辑 `database/seeds/HolidaysTableSeeder.php`：

```php
// 农历节日日期每年不同，需要手动更新
$holidays = [
    ['name' => '春节', 'holiday_date' => "{$currentYear}-01-29"],  // 更新为实际日期
    ['name' => '端午节', 'holiday_date' => "{$currentYear}-06-22"],
    ['name' => '中秋节', 'holiday_date' => "{$currentYear}-09-29"],
    // ...
];
```

---

## 💡 最佳实践

### 开发环境

```bash
# 频繁重置数据库
php artisan migrate:fresh --seed

# 或使用别名
alias mfs="php artisan migrate:fresh --seed"
```

### 测试环境

在测试类中使用：

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;  // 自动运行种子

    public function test_example()
    {
        // 测试代码
        $this->assertDatabaseHas('roles', ['name' => '超级管理员']);
    }
}
```

### 生产环境

**⚠️ 警告**：不要在生产环境运行 `db:seed`，会清空数据！

如需在生产环境初始化：

```bash
# 1. 仅运行迁移
php artisan migrate

# 2. 手动运行SQL脚本
mysql -u root -p dental_medical_management < database/seed_initial_data.sql

# 3. 然后手动调整数据（价格、公司信息等）
```

---

## 📚 相关文档

- **Seeder 详细说明**：`database/seeds/README.md`
- **数据库通用设置**：`database/DATABASE_SETUP.md`
- **Laravel 官方文档**：https://laravel.com/docs/5.8/seeding

---

## 🎯 常见问题（中国地区特定）

### Q1：农历节假日日期如何更新？

A：每年需要手动更新 `HolidaysTableSeeder.php` 中的农历节日日期（春节、清明、端午、中秋）。

参考：国务院办公厅每年发布的节假日安排通知。

### Q2：医疗服务价格是否符合中国市场？

A：脚本中的价格仅为示例，实际部署时需要根据：
- 当地市场行情
- 诊所定位（高端/中端/普通）
- 成本核算
进行调整。

### Q3：会计科目是否符合中国会计准则？

A：基本科目符合《企业会计准则》，但实际使用时可能需要：
- 添加更详细的二级科目
- 根据行业特点调整
- 咨询专业会计师

### Q4：保险索赔率如何确定？

A：脚本中的索赔率（基础70%、中等60%、高端50%）仅为示例。

实际需要：
1. 与各保险公司签订合作协议
2. 根据协议设置具体索赔比例
3. 定期更新索赔率配置

### Q5：如何添加医保？

可以添加：
- 职工医保
- 居民医保
- 异地医保

方法：
```bash
# 编辑 InsuranceCompaniesTableSeeder.php 添加医保类型
# 然后运行
php artisan db:seed --class=InsuranceCompaniesTableSeeder
php artisan db:seed --class=ClaimRatesTableSeeder
```

---

**维护者**：Dental Medical Management System Team
**适用版本**：Laravel 5.8
**最后更新**：2024
**适用地区**：中国
