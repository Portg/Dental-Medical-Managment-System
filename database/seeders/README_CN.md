# 数据库初始化数据说明

本目录包含牙科门诊管理系统的数据库种子文件。

## 运行方式

```bash
# 运行所有Seeder（推荐）
php artisan db:seed

# 重置数据库并运行Seeder
php artisan migrate:fresh --seed

# 运行单个Seeder
php artisan db:seed --class=MedicalServicesSeeder
```

## Seeder 列表

### 1. 基础配置

| Seeder | 说明 | 数据量 |
|--------|------|--------|
| BranchesTableSeeder | 分支机构 | 1条（主院） |
| RolesTableSeeder | 用户角色 | 5个角色 |
| PermissionsTableSeeder | 系统权限 | 24个权限 |
| UsersTableSeeder | 默认用户 | 1个管理员 |
| DefaultRolePermissionsSeeder | 角色权限关联 | 自动分配 |

### 2. 业务数据

| Seeder | 说明 | 数据量 |
|--------|------|--------|
| MedicalServicesSeeder | 口腔诊疗服务项目 | 85个项目 |
| ExpenseCategoriesSeeder | 费用分类 | 45个分类 |
| InventoryCategoriesSeeder | 库存分类 | 若干分类 |
| InsuranceCompaniesTableSeeder | 保险公司 | 6家公司 |
| ChartOfAccountsTableSeeder | 会计科目 | 5类29项 |

### 3. 辅助数据

| Seeder | 说明 | 数据量 |
|--------|------|--------|
| LeaveTypesTableSeeder | 休假类型 | 12种类型 |
| HolidaysTableSeeder | 2026年节假日 | 29天 |
| PatientTagsSeeder | 患者标签+来源 | 8标签+10来源 |
| MedicalTemplatesSeeder | 病历模板+常用短语 | 7模板+29短语 |

## 运行顺序

DatabaseSeeder 会按以下顺序执行：

```
1. 基础配置
   ├── BranchesTableSeeder      # 分支机构
   ├── RolesTableSeeder         # 角色
   └── PermissionsTableSeeder   # 权限

2. 用户数据
   └── UsersTableSeeder         # 创建管理员账户

3. 角色权限
   └── DefaultRolePermissionsSeeder  # 分配默认权限

4. 业务数据
   ├── MedicalServicesSeeder        # 诊疗服务项目
   ├── ExpenseCategoriesSeeder      # 费用分类
   ├── InventoryCategoriesSeeder    # 库存分类
   ├── InsuranceCompaniesTableSeeder # 保险公司
   └── ChartOfAccountsTableSeeder   # 会计科目

5. 辅助数据
   ├── LeaveTypesTableSeeder    # 休假类型
   ├── HolidaysTableSeeder      # 节假日
   ├── PatientTagsSeeder        # 患者标签和来源
   └── MedicalTemplatesSeeder   # 病历模板和常用短语
```

## 默认账户

| 角色 | 邮箱 | 密码 |
|------|------|------|
| 超级管理员 | admin@example.com | password |

> ⚠️ **请登录后立即修改默认密码！**

---

## 服务项目分类

MedicalServicesSeeder 包含以下口腔诊疗服务分类：

| 分类 | 项目示例 | 价格范围 |
|------|----------|----------|
| 检查诊断 | 口腔检查、X光片、CBCT | ¥30-500 |
| 洁牙预防 | 超声波洁牙、涂氟、窝沟封闭 | ¥100-400 |
| 补牙充填 | 树脂补牙、嵌体、贴面 | ¥150-2500 |
| 根管治疗 | 前牙/前磨牙/磨牙根管治疗 | ¥500-2500 |
| 拔牙 | 乳牙/恒牙/智齿拔除 | ¥100-1500 |
| 牙周治疗 | 基础治疗、翻瓣术 | ¥300-2000 |
| 修复 | 全瓷冠、烤瓷冠、义齿 | ¥200-8000 |
| 种植 | 种植体、骨粉、上颌窦提升 | ¥1500-80000 |
| 正畸 | 金属/陶瓷/隐形矫正 | ¥300-50000 |
| 儿童口腔 | 儿童涂氟、预成冠、早期矫正 | ¥80-8000 |
| 美容牙科 | 冷光美白、瓷贴面 | ¥800-3000 |
| 口腔外科 | 囊肿摘除、系带修整 | ¥500-5000 |

---

## 费用分类

ExpenseCategoriesSeeder 包含以下费用分类：

| 大类 | 具体分类 |
|------|----------|
| 人力成本 | 工资薪酬、社保公积金、绩效奖金、培训费用、招聘费用、员工福利 |
| 房租物业 | 房租、物业费、装修维护 |
| 水电能耗 | 电费、水费、燃气费、暖气/空调费 |
| 医疗耗材 | 一次性耗材、口腔材料、种植材料、正畸材料、修复材料、药品、消毒用品 |
| 设备资产 | 设备采购、设备维修、器械采购、办公设备、办公用品、家具购置 |
| 运营费用 | 网络通讯、软件服务、保险费用、税费、银行手续费、快递物流 |
| 市场营销 | 广告宣传、平台推广、活动费用、礼品采购 |
| 行政管理 | 差旅费、餐饮招待、车辆费用、会议费 |
| 技工加工 | 义齿加工、正畸加工 |
| 其他 | 法律服务、审计服务、杂项支出 |

---

## 节假日说明

HolidaysTableSeeder 包含2026年中国法定节假日：

| 节日 | 日期 | 天数 |
|------|------|------|
| 元旦 | 1月1日-1月3日 | 3天 |
| 春节 | 1月26日-2月1日 | 7天 |
| 清明节 | 4月4日-4月6日 | 3天 |
| 劳动节 | 5月1日-5月5日 | 5天 |
| 端午节 | 5月30日-6月1日 | 3天 |
| 中秋节+国庆节 | 10月1日-10月8日 | 8天 |

---

## 休假类型

LeaveTypesTableSeeder 包含中国常用休假类型：

| 类型 | 最大天数 | 说明 |
|------|----------|------|
| 年假 | 15天 | 根据工龄1-15天 |
| 病假 | 180天 | 医疗期 |
| 产假 | 158天 | 基础98天+奖励假 |
| 陪产假 | 15天 | 各地不同 |
| 事假 | 30天 | 企业规定 |
| 调休 | 30天 | 加班调休 |
| 婚假 | 30天 | 法定3天+晚婚假 |
| 丧假 | 3天 | 直系亲属 |
| 育儿假 | 10天 | 各地不同 |
| 护理假 | 20天 | 独生子女父母护理 |
| 哺乳假 | 180天 | 每天1小时 |
| 工伤假 | 365天 | 停工留薪期 |

---

## 常见问题

### 问题 1: "Class 'xxxSeeder' not found"

```bash
# 重新生成自动加载文件
composer dump-autoload
php artisan db:seed
```

### 问题 2: 外键约束冲突

```bash
# 使用 migrate:fresh 重置数据库
php artisan migrate:fresh --seed
```

### 问题 3: 数据库连接错误

1. 检查 `.env` 文件中的数据库配置
2. 确保数据库已创建
3. 测试连接：`php artisan migrate:status`

---

## 注意事项

1. **执行顺序**：Seeder有依赖关系，建议使用 `php artisan db:seed` 自动处理
2. **数据清空**：部分Seeder使用 `truncate()` 清空现有数据
3. **生产环境**：不建议在生产环境运行全部Seeder，仅运行必要的初始化数据
4. **自定义数据**：可根据实际情况修改价格、分类等数据
