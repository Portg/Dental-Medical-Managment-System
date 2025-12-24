# Laravel 数据库种子使用说明

本目录包含牙科医疗管理系统的数据库种子文件（中国地区版本）。

## 📁 文件列表

### 主种子文件
- **DatabaseSeeder.php** - 主种子文件，协调所有子种子的执行顺序

### 子种子文件（按执行顺序）

1. **RolesTableSeeder.php** - 系统角色种子
   - 超级管理员、医生、护士、前台接待、药剂师、会计、化验师

2. **UsersTableSeeder.php** - 默认用户账户种子
   - 4个测试账户，密码统一为 `password`

3. **BranchesTableSeeder.php** - 分支机构种子
   - 总院、分院一部、分院二部

4. **LeaveTypesTableSeeder.php** - 休假类型种子
   - 年假、病假、产假、陪产假、事假、调休、婚假、丧假

5. **ExpenseCategoriesTableSeeder.php** - 费用分类种子
   - 水电费、房租、工资薪酬、医疗耗材等12个分类

6. **ChartOfAccountsTableSeeder.php** - 会计科目种子
   - 5个会计科目分类，29个科目项目

7. **MedicalServicesTableSeeder.php** - 医疗服务种子
   - 15种牙科医疗服务，价格单位为人民币（元）

8. **InsuranceCompaniesTableSeeder.php** - 保险公司种子
   - 6家中国主要保险公司

9. **ClaimRatesTableSeeder.php** - 索赔率种子
   - 90条索赔率配置（6家保险公司 × 15种医疗服务）
   - 基础诊疗70%、中等诊疗60%、高端诊疗50%

10. **HolidaysTableSeeder.php** - 法定节假日种子
    - 13个中国法定节假日（当前年份）

11. **AccountingEquationsTableSeeder.php** - 会计方程式种子
    - 4条基本会计方程式

12. **PatientsTableSeeder.php** - 患者数据种子（测试用）
    - 可选，用于生成测试患者数据

---

## 🚀 使用方法

### 方法一：运行所有种子（推荐）

```bash
# 运行所有种子文件
php artisan db:seed

# 或者重置数据库并运行种子
php artisan migrate:fresh --seed
```

### 方法二：运行特定种子

```bash
# 仅运行角色种子
php artisan db:seed --class=RolesTableSeeder

# 仅运行用户种子
php artisan db:seed --class=UsersTableSeeder

# 运行多个种子
php artisan db:seed --class=RolesTableSeeder
php artisan db:seed --class=UsersTableSeeder
php artisan db:seed --class=BranchesTableSeeder
```

### 方法三：在代码中调用

```php
// 在控制器或其他地方调用
Artisan::call('db:seed');

// 或调用特定种子
Artisan::call('db:seed', ['--class' => 'RolesTableSeeder']);
```

---

## 📊 数据汇总

执行完所有种子后，将创建以下数据：

| 数据类型 | 数量 | 说明 |
|---------|------|------|
| 用户角色 | 7 | 超级管理员、医生、护士等 |
| 默认用户 | 4 | 管理员、医生、护士、前台 |
| 分支机构 | 3 | 总院、分院一部、分院二部 |
| 休假类型 | 8 | 年假、病假、产假等 |
| 费用分类 | 12 | 水电费、房租、工资等 |
| 会计科目分类 | 5 | 资产、负债、权益、收入、费用 |
| 会计科目项目 | 29 | 库存现金、银行存款等 |
| 医疗服务 | 15 | 口腔检查、洁牙、补牙等 |
| 保险公司 | 6 | 中国人寿、平安、太平洋等 |
| 索赔率配置 | 90 | 6家保险 × 15种服务 |
| 法定节假日 | 13 | 元旦、春节、国庆等 |
| 会计方程式 | 4 | 基本会计恒等式 |

---

## 🔑 默认登录凭证

| 角色 | 邮箱 | 密码 | 姓名 |
|------|------|------|------|
| 超级管理员 | admin@dental.com | password | 张管理员 |
| 医生 | doctor@dental.com | password | 李医生 |
| 护士 | nurse@dental.com | password | 王护士 |
| 前台 | reception@dental.com | password | 赵前台 |

**⚠️ 重要提示**: 请在首次登录后立即修改默认密码！

---

## 💡 注意事项

### 1. 执行顺序
种子文件有依赖关系，必须按顺序执行：
- `DatabaseSeeder.php` 已经按正确顺序编排了所有种子
- 建议使用 `php artisan db:seed` 自动处理执行顺序

### 2. 数据清空
所有种子文件都使用 `truncate()` 清空现有数据，请谨慎使用：
```php
DB::table('roles')->truncate();  // 会清空表中所有数据！
```

**生产环境警告**: 不要在生产环境运行种子，会导致数据丢失！

### 3. 外键约束
种子文件会自动处理外键依赖：
- 先创建 roles（角色）
- 再创建 users（用户，依赖角色）
- 最后创建 branches（分支，依赖用户）

### 4. 农历节假日
`HolidaysTableSeeder` 中的农历节日（春节、清明、端午、中秋）日期仅为示例：
```php
// 这些日期每年不同，需要根据实际年份调整
['name' => '春节', 'holiday_date' => "{$currentYear}-02-10"]
```

### 5. 价格单位
医疗服务价格使用人民币（元）：
```php
['name' => '口腔检查', 'price' => 50.00]  // 50元
['name' => '种植牙', 'price' => 8000.00]   // 8000元
```

---

## 🔧 自定义种子

### 创建新的种子文件

```bash
# 使用 artisan 命令创建种子
php artisan make:seeder CustomTableSeeder
```

### 在 DatabaseSeeder 中注册

编辑 `DatabaseSeeder.php`，添加新种子：

```php
public function run()
{
    // ... 其他种子

    // 添加你的自定义种子
    $this->call(CustomTableSeeder::class);
}
```

---

## 🐛 常见问题

### 问题 1: "Class 'RolesTableSeeder' not found"

**原因**: Laravel 无法找到种子类

**解决方案**:
```bash
# 重新生成自动加载文件
composer dump-autoload

# 然后重新运行种子
php artisan db:seed
```

---

### 问题 2: "SQLSTATE[23000]: Integrity constraint violation"

**原因**: 外键约束冲突

**解决方案**:
```bash
# 使用 migrate:fresh 重置数据库
php artisan migrate:fresh --seed
```

---

### 问题 3: "Nothing to seed"

**原因**: 数据库连接配置错误

**解决方案**:
1. 检查 `.env` 文件中的数据库配置
2. 确保数据库已创建
3. 测试数据库连接：
```bash
php artisan migrate:status
```

---

### 问题 4: 种子数据已存在

**原因**: truncate() 失败或跳过

**解决方案**:
```bash
# 方式1: 重置数据库（删除所有数据）
php artisan migrate:fresh --seed

# 方式2: 手动清空表
php artisan tinker
>>> DB::table('roles')->truncate();
>>> DB::table('users')->truncate();
```

---

## 📝 最佳实践

### 1. 开发环境
```bash
# 开发时经常需要重置数据库
php artisan migrate:fresh --seed
```

### 2. 测试环境
```bash
# 使用 RefreshDatabase trait
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;  // 自动运行种子
}
```

### 3. 生产环境
**绝对不要在生产环境运行 db:seed！**

如果必须添加初始数据：
```bash
# 仅运行特定的安全种子
php artisan db:seed --class=RolesTableSeeder
```

### 4. 版本控制
- 所有种子文件都应纳入 Git 版本控制
- 不要在种子中硬编码敏感信息
- 使用环境变量处理不同环境的配置

---

## 🔄 更新种子数据

如果需要更新种子数据：

1. 修改对应的种子文件
2. 重新运行种子：
```bash
php artisan db:seed --class=UpdatedSeederName
```

3. 或重置整个数据库：
```bash
php artisan migrate:fresh --seed
```

---

## 📚 相关文档

- [Laravel 数据库种子官方文档](https://laravel.com/docs/5.8/seeding)
- [数据库迁移指南](../migrations/)
- [数据库设置文档](../DATABASE_SETUP.md)

---

## 💬 获取帮助

如遇到问题：
1. 查看 Laravel 日志：`storage/logs/laravel.log`
2. 检查数据库连接配置
3. 参考本文档的常见问题部分
4. 查看 Laravel 官方文档

---

**最后更新**: 2024
**版本**: 1.0
**适用地区**: 中国
