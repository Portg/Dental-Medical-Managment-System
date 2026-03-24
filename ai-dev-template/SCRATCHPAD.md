# SCRATCHPAD.md — 跨会话连续性记录

## 使用规则
1. 每次 AI 会话结束前，复制下方模板让 AI 填写
2. 新记录插入文件顶部（最新在上）
3. 超过 10 条时压缩最老 7 条为「历史摘要」
4. 关键决策必须填写「AI 推理链」

---

## 记录模板

```
---
日期：____  任务类型：____  关联切片：____
使用的 Prompt 版本：[如 generate-api/v1.0]

### 本次完成了什么

### 关键决策
| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|

### AI 推理链（关键决策必填）
# 格式要求：每个关键决策，按以下结构输出推理过程
#
# 决策：[决策名称]
# 1. 我读取了 [文件/规则]
# 2. 我注意到 [关键约束，引用原文]
# 3. 我考虑了 [方案A] 和 [方案B]
# 4. 方案A 的问题：[具体原因]
# 5. 因此我选择了 [方案B]，因为 [与约束的对应关系]

### 放弃的方案
| 方案描述 | 放弃原因 |
|----------|---------|

### 遗留问题（下次会话继续）
- [ ]

### Spec 变更建议
- 文件：____ 内容：____

### Prompt 效果反馈
# 版本：____ 评价：[好用 / 有问题] 具体：____
---
```

---
## [2026-03-24] Session: 消毒登记模块实现（Task 1~10 全部完成）

任务类型：executing-plans  关联切片：消毒管理

### 本次完成了什么
- **Task 1** `2026_03_23_000004_create_sterilization_tables.php` — 4 张表迁移成功
- **Task 2** 4 个模型：`SterilizationKit / KitInstrument / Record / Usage`
- **Task 3** TDD：写测试 → Red → 实现 `SterilizationService` → Green（9 tests pass）
- **Task 4** `SterilizationKitService` + `SterilizationKitController`（CRUD + 明细同步）
- **Task 5** `SterilizationController`（CRUD + 使用登记 + 撤销 + CSV 导出 + edit 回填）
- **Task 6** `PermissionsTableSeeder`：新增 view/manage-sterilization；`MenuItemsSeeder`：新增「诊所事务」(sort_order=35) + `seedClinicAffairs()`；权限分配到 SADN 角色
- **Task 7** 13 条路由（含 export/edit/use/revoke 前置防参数冲突）；zh-CN/en sterilization.php；menu.php 补 clinic_affairs/sterilization_management
- **Task 8** 6 个 Blade 视图：index + _tab_records + _tab_kits + _modal_record + _modal_kit + _modal_use
- **Task 9** `public/include_js/sterilization.js`（DataTable + 3 个弹框 + 过滤器）+ `public/css/sterilization.css`
- **Task 10** 9 tests all pass，13 routes verified，1 commit `56ad1ef`

### 关键决策
| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| User::factory() 需要 role_id | setUp 先 `Role::create(['slug'=>'admin'])` 再传 role_id | users 表 role_id FK 非空；参照 BillingStockOutTest 模式 |
| `$user->name` → null | 改用 `$user->full_name`（accessor）| User 模型无 name 字段，使用 getFullNameAttribute() |
| 有效期测试精度 | `assertEqualsWithDelta(90, round(...), 1)` | 测试运行时 now() 有微秒偏差，(int)diffInDays 截断得 89 |
| export 路由前置 | `sterilization/export` 在 resource 之前注册 | 防止 Laravel 把 `export` 解析为 `{sterilization}` 参数 |

### AI 推理链（关键决策）

决策：User factory role_id
1. 读取了 BillingStockOutTest 的 setUp 模式
2. 发现 users 表有 `users_role_id_foreign` FK 约束
3. 考虑了：A) 修改 UserFactory 加默认 role_id；B) 测试中手动创建 Role 传入
4. 方案A 需修改共享 factory 可能影响其他测试
5. 选择方案B：每个 setUp 创建一个最小 Role，通过 factory 参数传入

决策：export 路由前置
1. 读取了 Plan Task 7 注意事项
2. 注意到「sterilization/export 必须在 resource 之前」
3. Laravel resource 会把 `sterilization/{sterilization}` 匹配所有 GET，包括 /export
4. 前置注册 named route 确保 export 不被参数路由劫持

### 放弃的方案
| 方案描述 | 放弃原因 |
|----------|---------|
| 修改 UserFactory 加默认 role_id | 会影响其他测试的隔离性，且 factory 需要 DB 已有 roles 数据 |
| 使用 Maatwebsite\Excel 做 export | 为 CSV stream 实现更轻量，且无需额外依赖；Excel 导出可后续迭代 |

### 遗留问题（下次会话继续）
- [ ] 浏览器手工测试验收（登录真实账号验证菜单/DataTable/弹框/使用登记流程）
- [ ] export 当前为 CSV stream；若需要 Excel 格式可用 Maatwebsite\Excel 迭代
- [ ] sterilization_usages 撤销后页面无刷新提示——已实现 JS revokeUse 但未暴露入口按钮（used 记录的 action 列无撤销按钮）

### Spec 变更建议
- 无需更新，已按 2026-03-23-billing-sterilization-design.md 完整实现

---
## [2026-03-23] Session: 收费项目管理升级 + 消毒登记功能 — 实现计划

任务类型：writing-plans  关联切片：收费项目、消毒管理

### 本次完成了什么
- 生成两份详细实现计划：
  - `docs/superpowers/plans/2026-03-23-billing-services-upgrade.md`（13 个 Task，含 TDD 测试步骤）
  - `docs/superpowers/plans/2026-03-23-sterilization-module.md`（10 个 Task，含 TDD 测试步骤）
- 对两份计划各跑 1 轮 reviewer，修复 4 个阻塞问题
- 计划已通过最终 review（状态：Approved）

### 关键决策

| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| billing 和 sterilization 分为两个独立计划 | 两个计划文件 | 两个子系统可独立执行；共享 PermissionSeeder 步骤已在各计划中标注协调方式 |
| import/export 路由权限 | import 用 `can:import-medical-services`，export 用 `can:manage-medical-services` | Reviewer 发现缺失权限守卫，spec §八 明确定义了两个独立权限 |
| `_modal_batch_price` 作为独立 blade partial | 独立文件，在 index 中 `@include` | 原计划遗漏该文件，导致 JS `#batchPriceModal` 无对应 HTML，reviewer 发现后补充 |
| 批次号并发方案 | `SELECT MAX... FOR UPDATE` 行锁 | 诊所并发量低；设计文档已确认；写入 SterilizationService 测试用例验证 |

### AI 推理链（关键决策）

决策：billing 和 sterilization 分为两个独立计划
1. 读取了 writing-plans skill 中「Scope Check」章节
2. 注意到「multiple independent subsystems should be separate plans」
3. 考虑了：A) 合并为一个大计划；B) 拆分为两个独立计划
4. 方案A 问题：Task 数量超过 20 个，一次执行容易失焦；两个功能 UI 完全无关
5. 选择方案B；共享的 PermissionSeeder 步骤通过在计划中加「前提条件」说明协调

### 放弃的方案

| 方案描述 | 放弃原因 |
|----------|---------|
| billing + sterilization 共享一个计划 | 超过 20 个 Task，两个 UI 无关，reviewer 会更难检查范围 |
| import 不加权限中间件 | Reviewer 明确指出 spec §八 定义了 `import-medical-services` 权限，需强制守卫 |

### 遗留问题（下次会话继续）
- [ ] 执行 billing-services-upgrade 计划（Task 1~13）
- [ ] 执行 sterilization-module 计划（Task 1~10）
- [ ] 两个计划的 PermissionsTableSeeder 最终合并为一次 `db:seed` 跑，避免重复

---
## [2026-03-23] Session: 收费项目管理升级 + 消毒登记功能 — 设计文档

任务类型：设计 / Brainstorming  关联切片：收费项目、消毒管理

### 本次完成了什么
- 通过 Visual Companion（brainstorming skill）完成两个功能的完整设计对话
- 修复 `public/.htaccess` 缺失导致 Apache POST /login 404 的 bug
- 修复 `config/logging.php` slack 通道无条件加载问题
- 修复 `deploy/yakpro-po.cnf` 混淆排除 Http 目录
- 重构 `deploy/build.sh / build-installer.iss / build-README.md`：换用 laragon-wamp.exe 安装器流程
- 增强 `deploy/install-win.ps1`：Laragon 安装、ini 编辑器、端口诊断
- 写入设计文档：`docs/superpowers/specs/2026-03-23-billing-sterilization-design.md`
- 经过 spec 审查循环修复 P0×3 / P1×5 / P2×2 共 10 个问题
- 5 个分类 git commit 推送到远程

### 关键决策

| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| 收费项目分类存储 | 新增 `service_categories` 表 + `category_id` FK | 旧 varchar `category` 字段无法支持大类排序/隐藏/拖拽，需规范化 |
| 器械明细存储 | 新增 `sterilization_kit_instruments` 子表 | JSON 不支持独立增删改排序，用户明确要求不用 JSON |
| 消毒导航位置 | 新增一级菜单「诊所事务」(sort_order=35) | 竞品用"事务"命名；为未来患者召回、诊室日志预留扩展位；语义独立于临床和财务 |
| 冗余字段填充 | Service 层自动填充，前端不传入 | 避免前端传入导致数据不一致；历史记录在软删除后仍完整 |
| 批次号并发安全 | `SELECT FOR UPDATE` 行锁 | 简单可靠；诊所并发量低，无需引入 Redis |
| 消毒管理权限 | 拆分 view + manage 两级，Doctor 两级均持有 | 兼顾有护士（职责分工）和无护士（医生全流程）两种诊所场景 |
| status 状态机 | 实时判断过期（不靠定时任务），使用后更新，软删除回滚 | 定时任务有延迟窗口，实时判断更准确；软删除支持撤销误录 |

### AI 推理链（关键决策）

决策：消毒导航位置
1. 读取了 MenuItemsSeeder，发现现有 6 个一级菜单：今日工作/患者中心/诊疗中心/运营中心/数据中心/系统管理
2. 注意到竞品（口腔云）用"事务→消毒"的路径，用户原始需求也引用了"事务"
3. 考虑了：A) 挂在诊疗中心下（临床语义合适但臃肿）；B) 新增「诊所事务」；C) 挂在运营中心（财务语义不对）
4. 方案A 问题：诊疗中心已有4个子组，再加略显拥挤，且无法为未来「患者召回」等功能预留落点
5. 选择方案B，sort_order=35 插在诊疗和运营之间，名称用「诊所事务」比「事务管理」更具体

决策：is_favorite 语义
1. 读取了 medical_services 现有字段，发现已有 `category` varchar
2. 注意到用户需求"常用项目"没有明确说是个人还是全局
3. 考虑了：全局布尔字段 vs 个人收藏关联表（user_id+service_id）
4. 个人收藏方案需要新增关联表，增加复杂度；诊所场景下"常用"通常是诊所级别的运营决策
5. 选择全局布尔字段，Anti-Goals 明确标注不做个人收藏

### 放弃的方案

| 方案描述 | 放弃原因 |
|----------|---------|
| 消毒使用记录不加 deleted_at | 医疗合规场景需要支持撤销误录，物理删除会断追溯链 |
| 批次号用 Redis INCR | 诊所系统无 Redis 依赖，DB 行锁已足够 |
| `manage-sterilization` 不含 Doctor | 无专职护士时医生需要承担全流程，权限设计要适配小诊所场景 |
| `service-categories` 路由不加前缀 | 会与 `billing/service-categories/{patientId}` 产生命名冲突 |

### 遗留问题（下次会话继续）
- [ ] 调用 `writing-plans` 生成两个功能的详细实现计划（用户确认 spec 后进行）
- [ ] 实现迁移文件（含旧 category 字段数据回填逻辑）
- [ ] `InvoiceService::getServiceCategoryTree()` 改为基于 `category_id` join 查询
- [ ] `billing/service-categories/{patientId}` 路由与新 `admin/service-categories` 联调

### Spec 变更建议
- 文件：`docs/superpowers/specs/2026-03-23-billing-sterilization-design.md`  内容：已完成，包含数据模型/路由/视图/导航/权限/实现顺序

---
## [2026-03-16] Session: Blade Tier3 全量清理 + AG 修复 + 功能增强

### 完成内容

#### 1. AG-065 bcmath 修复
- `App/Services/InvoiceService.php` → `createBillingInvoice()` 浮点累加改用 bcmath
- `App/Services/InvoicePaymentService.php` → `processMixedPayment()` 全部金额运算改用 bcmath（`bcadd/bcsub/bcmul/bccomp`）
- 两处 catch 块补全 `Log::error()` + 返回 `__('messages.error_occurred')`

#### 2. AG-073 排班删除守卫
- `app/Services/DoctorScheduleService.php` → 删除前检查 `WaitingQueue` 是否有活跃患者

#### 3. LabCaseStatisticsReportService 修复
- 硬编码 `"completed"/"rework"` 字符串 → `LabCase::STATUS_COMPLETED / STATUS_REWORK`
- 硬编码中文 → `__('lab_cases.unknown_lab')` / `__('lab_cases.unassigned_doctor')`

#### 4. 预约分析报表增强
- `App/Services/AppointmentAnalyticsReportService.php` → 新增 `$sourceId` / `$tagIds` 过滤维度
- `applyPatientFilters()` 方法支持 JOIN patients 表按来源/标签筛选
- `resources/views/reports/appointment_analytics_report.blade.php` → 新增来源/标签下拉筛选器

#### 5. Blade Tier3 全量清理（内联 CSS/JS 分离）

新建/更新外部 JS 文件（共 ~20 个）：

| 外部 JS 文件 | 对应 Blade | 新建/已有 |
|------------|-----------|---------|
| `billing_report.js` | `reports/billing_report.blade.php` | 新建 |
| `financial_detail_report.js` | `reports/financial_detail_report.blade.php` | 新建 |
| `financial_calendar.js` | `reports/financial_calendar.blade.php` | 新建 |
| `lab_case_statistics_report.js` | `reports/lab_case_statistics_report.blade.php` | 新建 |
| `unpaid_invoices_report.js` | `reports/unpaid_invoices_report.blade.php` | 新建 |
| `doctor_report.js` | `reports/doctor_report.blade.php` | 新建 |
| `patient_report.js` | `reports/patient_report.blade.php` | 新建 |
| `waiting_queue.js` | `waiting_queue/index.blade.php` | 已有→已接入 |
| `doctor_queue.js` | `waiting_queue/doctor_queue.blade.php` | 新建 |
| `doctor_schedules_index.js` | `doctor_schedules/index.blade.php` | 新建 |
| `refunds_create.js` | `refunds/create.blade.php` | 已有→已接入 |
| `service_consumables.js` | `inventory/service_consumables/index.blade.php` | 已有→已接入 |
| `individual_payslips.js` | `payslips/individual_payslips.blade.php` | 新建 |
| `doctor_claim_payments.js` | `doctor_claims/payments/index.blade.php` | 新建 |
| `profile_index.js` | `profile/index.blade.php` | 新建 |
| `satisfaction_surveys_index.js` | `satisfaction_surveys/index.blade.php` | 新建 |
| `medical_cards_show.js` | `medical_cards/show.blade.php` | 新建 |
| `charts_of_accounts_index.js` | `charts_of_accounts/index.blade.php` | 新建 |
| 9个报表页 JS | appointment_analytics, business_cockpit 等 | 已有→已接入 |

#### 6. 遗留
- `roles/show.blade.php`（836行）— 延后处理
- `reports/cash_summary_report.blade.php`（3行 datepicker）— 低于阈值，保留内联

### 关键决策
- AG-065 bcmath: 使用 `bccomp()` 替代 `>` 比较浮点字符串
- Blade 桥接模式: `window.XxxConfig = {}` + `LanguageManager.loadFromPHP(@json(__('module')), 'module')` 放在 blade，逻辑全部放外部 JS
- 提取阈值: < 15 行纯逻辑的内联脚本不提取（如 datepicker init、TODO stub）

---

日期：2026-03-16  任务类型：Blade Tier1 清理  关联切片：全局前端规范

### 本次完成了什么

**Blade Tier1 清理（Week1-Day1）— 8 个核心页面全部完成**
- `invoices/index.blade.php` → `public/include_js/invoices_index.js`（460 行内联 JS 提取）
- `waiting_queue/index.blade.php` → `public/css/waiting-queue.css`（72 行 CSS 提取）+ `public/include_js/waiting_queue.js`（225 行 JS 提取）
- `users/index.blade.php` → `public/include_js/users_index.js`（330 行 JS 提取）
- `medical_cases/index.blade.php` → `public/include_js/medical_cases_index.js`（80 行 JS 提取）
- `refunds/create.blade.php` → `public/include_js/refunds_create.js`（110 行 JS 提取）
- `medical_treatment/index.blade.php` → `public/include_js/medical_treatment_index.js`（35 行 JS 提取）
- `medical_cases/show.blade.php` → `public/include_js/medical_cases_show.js`（119 行 JS 提取）
- `today_work/index.blade.php` → `public/include_js/today_work_main.js`（380 行 JS 提取）

各 Blade 文件内联 script 块残留 4~24 行（仅保留 PHP config 初始化），符合规范。

### 关键决策
| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| Blade `{{ __() }}` 在 JS 中的处理 | 在 Blade 保留 loadAllFromPHP，外部 JS 用 LanguageManager.trans() | 翻译字符串不能硬编码在外部 JS 文件中，必须运行时注入 |
| PHP 生成值（URL/locale/csrf）传入外部 JS | window.XxxConfig 对象在内联 script 中初始化 | 外部 JS 文件无法包含 Blade 表达式，需通过全局变量桥接 |
| `@if(locale === 'zh-CN')` Blade 条件 | 在 Blade 传入 isZhCN boolean，外部 JS 用 if/else | Blade 条件会改变 JS 代码结构（DataTable columns 数组），必须在运行时判断 |
| today_work 分离成 today_work_main.js | 独立新文件，不合并到已有 JS 文件 | 已有 5 个独立功能模块文件（tabs/kanban/drawer…），主协调逻辑应有单独入口 |

### AI 推理链

决策：PHP 生成值传递策略
1. 读取 CLAUDE.md：「JS 中使用 LanguageManager.trans() 获取翻译，不用 Blade `{{ __() }}`」
2. 注意到 JS 文件（public/include_js/*.js）是静态文件，无法包含 Blade 表达式
3. 考虑方案A：在外部 JS 文件中保留 Blade 表达式（让 Blade 编译 JS）→ 需要把 JS 文件移入 resources/ 并注册路由，复杂度高
4. 考虑方案B：在 Blade 文件的最小化内联 script 块中初始化 window.XxxConfig = {...}，外部 JS 引用配置对象
5. 选择方案B：零架构变更，Blade 只保留 PHP-to-JS 的数据桥接，所有逻辑在外部 JS

决策：`@if` Blade 条件块的处理（users_index.js）
1. 读取 users/index.blade.php：DataTable columns 数组有 `@if(app()->getLocale() === 'zh-CN')` 分支，生成不同的列定义
2. 注意到这不是翻译问题，而是 JS 代码结构随 locale 变化
3. 考虑方案A：保留 `@if` 块在 Blade 内联 script 中，只把无条件代码提取到外部 JS → 会把 DataTable 初始化分割成两个文件
4. 考虑方案B：在 Blade 传入 `window.UsersConfig.isZhCN = {{ ... ? 'true' : 'false' }}`，外部 JS 用 `if (window.UsersConfig.isZhCN)` 动态构建 columns 数组
5. 选择方案B：DataTable 初始化完整在外部 JS，逻辑更清晰，运行时 locale 判断无性能损耗

### 放弃的方案
| 方案描述 | 放弃原因 |
|----------|---------|
| 把 JS 文件移入 resources/js/ 让 Blade 编译 | 需要注册路由或构建步骤，超出本次任务范围 |
| patients/show.blade.php 的 33 行 init 代码提取 | 已有 5 个外部文件，剩余 33 行全是 Blade PHP 变量注入，无法分离 |
| doctor_schedules/index.blade.php 的 14 行提取 | 全部是 LanguageManager.loadAllFromPHP + ScheduleGrid.init()，提取意义不大 |

### 遗留问题（下次会话继续）
- [x] **Week1-Day3**: 医生自查绩效报表（doctor-report 页加 Auth::id() 过滤，仅显示自己数据）
- [x] **Week1-Day4**: AG-070 最后超管保护 + changeStatus 操作日志（OperationLog::log）
- [x] **Week1-Day5**: 供应商字段扩展（phone/email/address/contact/证照）+ AG-071 OCR文件类型验证
- [x] **Week2-Day1**: AG-069 发票追加服务的增量库存扣减
- [x] **Week2-Day2**: AG-072 prescription_no 唯一约束迁移（migration + withTrashed + 1062 retry）
- [x] **Week2-Day3**: 3个P0流程 Feature Test（Invoice+库存 / Invoice追加AG-069 / 删除+回滚）
- [x] **Week2-Day4**: RefundService 并发加固（lockForUpdate）+ 3个未验证报表人工验证
- [x] Blade Tier2 清理（25/25 全部完成）
  已完成：patients/index(1143行), patients/create(720行), quotations/index(361行), expenses/index(359行), medical_templates/index(262行), online_bookings/index(250行), doctor_claims/index(249行), treatment_plans/index(208行), payslips/index(279行), patient_tags/index(231行), patient_sources/index(228行), claim_rates/index(236行), medical_cards/index(240行), employee_contracts/index, salary_advances/index, leave_requests/index, commission_rules/index, quick_phrases/index, chairs/index, holidays/index, self_accounts/index, quotations/show/index, menu_items/index(+CSS), lab_cases/index(lab_case_list.js补接bridge), system_maintenance/index(+CSS)

---

日期：2026-03-16  任务类型：代码审查 + Bug修复 + 进度规划  关联切片：切片A/B/D/E

### 本次完成了什么

**代码审查（/review 所有未提交本地文件）**
- 审查 80+ 文件、~4000 行净增量，覆盖三个实现批次
- 输出分级问题报告：P0×1、P1×4（含1个误报）、P2×3、P3若干
- 安全审查：SQL注入/XSS/CSRF/并发均通过

**P0-P2 Bug 修复（/implement 按优先级修复）**
- `AppointmentsController::store()` — null 返回改为 `shift_max_patients_exceeded` 提示
- `StockOutService::rollbackBillingStockOut()` — 去除内层事务，消除 savepoint 静默吞异常风险
- `InvoiceService::invoiceListQuery()` + `getExportData()` — 加软删除患者过滤条件
- `OcrService::recognizeViaServer()` — fopen() 返回值检查，失败抛 RuntimeException
- `UsersController::changeStatus()` — 禁止自我离职 + null-safe find + 补 Auth import + 新增翻译 key
- 新增翻译 key：`users.cannot_resign_yourself`（zh-CN / en）

**进度规划（/spec-discovery 本工程优化改进进度）**
- Blade 清理分三级：Tier1（15个核心页面，部署前做）/ Tier2（25个，部署后）/ Tier3（~50个，积压）
- 识别 5 个 Anti-Goals 遗漏（AG-069~073）
- 输出 2 周部署路线图（Week1: 功能补全 / Week2: 部署准备）

### 关键决策
| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| rollbackBillingStockOut 事务处理 | 去除内层事务，由外层统一管理 | 内层 catch 会 rollback 到 savepoint 并吞掉异常，导致库存未回滚但发票已删除 |
| createAppointment 返回 null 的处理 | Controller 层 map null→shift_full 消息 | null 仅在班次已满时出现（DB异常会向上抛出），无需改 Service 签名 |
| Blade 清理策略 | Tier 分级，仅做 Tier1 | 90 个文件全量清理需40-60天，与部署目标不符；系统管理页面内联JS不影响核心业务 |

### AI 推理链

决策：rollbackBillingStockOut 去除内层事务
1. 读取 `InvoiceService::deleteInvoice()` — 已有外层 `DB::beginTransaction()`
2. 读取 `rollbackBillingStockOut()` — 内部又有 `DB::beginTransaction()/commit()/rollBack()`
3. Laravel 嵌套事务行为：内层 beginTransaction() → 创建 SAVEPOINT；内层 commit() → 仅 decrement 计数，不真正提交；内层 catch 中 rollBack() → 回滚到 SAVEPOINT，**异常被吞掉**
4. 问题：rollback 失败后外层不知情，继续 commit 发票删除 → 库存未恢复但发票已删
5. 选择方案：去除内层所有 begin/commit/rollback，异常向上传播，由外层事务统一处理

决策：AppointmentsController null 返回
1. 读取 `AppointmentService::createAppointment()` — `DB::transaction()` 内只有一处显式 `return null`（班次已满）
2. `DB::transaction()` 内部异常会被 re-throw，不会返回 null
3. 方案A：改 Service 返回 array{status, message} — 需改所有调用点，包括 API 层
4. 方案B：Controller 层 map null→specific message — 零侵入，语义准确
5. 选择方案 B

### 放弃的方案
| 方案描述 | 放弃原因 |
|----------|---------|
| rollbackBillingStockOut 保留内层事务 | savepoint 机制下内层异常被静默吞掉，库存回滚失败无法被外层捕获 |
| createAppointment 返回 array{status,error} | 需同步修改 API v1 的 AppointmentController，变更面扩大 |
| Blade 全量清理（90个文件） | 不符合「功能补全+部署准备」的阶段目标，性价比过低 |

### 遗留问题（下次会话继续）
- [ ] **Week1-Day1**: Blade Tier1 清理（patients/index、patients/create、medical_cases/index、medical_cases/show、medical_treatment/index、invoices/index、refunds/index、waiting_queue/index、today_work/index、doctor_schedules/index、users/index 等15页）
- [x] **Week1-Day3**: 医生自查绩效报表（doctor-report 页加 Auth::id() 过滤，仅显示自己数据）
- [x] **Week1-Day4**: AG-070 最后超管保护 + changeStatus 操作日志（OperationLog::log）
- [x] **Week1-Day5**: 供应商字段扩展（phone/email/address/contact/证照）+ AG-071 OCR文件类型验证
- [x] **Week2-Day1**: AG-069 发票追加服务的增量库存扣减
- [x] **Week2-Day2**: AG-072 prescription_no 唯一约束迁移（migration + withTrashed + 1062 retry）
- [x] **Week2-Day3**: 3个P0流程 Feature Test（Invoice+库存 / Invoice追加AG-069 / 删除+回滚）
- [x] **Week2-Day4**: RefundService 并发加固（lockForUpdate）+ 3个未验证报表人工验证
- [ ] P1已确认误报：DoctorScheduleService::checkTimeConflict() 已存在（line 273-302）

### Spec 变更建议
- 文件：intent.md  内容：新增 AG-069「发票追加服务时触发增量库存扣减」
- 文件：intent.md  内容：新增 AG-070「系统必须保留至少1个active超管」
- 文件：intent.md  内容：新增 AG-071「OCR上传必须验证MIME为image/*，限制5MB」
- 文件：intent.md  内容：新增 AG-072「prescription_no 须加唯一约束，防重试重复创建」
- 文件：intent.md  内容：新增 AG-073「排班删除前须检查当日WaitingQueue候诊记录」

---

日期：2026-03-15  任务类型：报表改造（续）+ 库房管理对比分析  关联切片：切片 B（收费）

### 本次完成了什么

**报表菜单重复清理（3 条迁移）**
- `2026_03_16_100004` — 删除 `procedures_income_report` 菜单（与 `general_income_report` 重复指向 billing-report）
- `2026_03_16_100005` — 删除 `patient_demographics_report` 菜单（与 `patient_source_report` 重复指向 patient-report）
- `2026_03_16_100006` — 删除 `doctor_workload_report` 菜单（与 `doctor_performance_report` 重复指向 doctor-report）

**财务明细 SQL 修复（FinancialDetailReportService）**
- `getRefunds()`: `refunds.user_id` → `_who_added`、`refunds.amount` → `refund_amount`、`refunds.reason` → `refund_reason`、join 链改为直接 `refunds.patient_id`、日期改为 `refund_date`
- `getExpenses()`: `expenses.description` → `purchase_no`

**报表 vs 参考系统对比（报表查询.md）**
- 完成 4.1~4.3 全部需求条目与本系统功能的逐项对比
- 输出缺失功能优先级表（高/中/低）

**第 1 周报表实现（3 个功能）**
- 现金汇总第 5 tab「按收费大类」: `CashSummaryReportService::byServiceCategory()` — join invoice_payments→invoices→invoice_items→medical_services.category
- 财务明细第 4 tab「员工收费明细」: `FinancialDetailReportService::getEmployeeBilling()` + `getCashiers()` + Controller + Blade + DataTable
- 未收款报表（新页面）: `UnpaidInvoicesReportController` + `unpaid_invoices_report.blade.php` + CSS + 路由 + 菜单迁移 100007
- 语言文件 zh-CN/en 各增 ~10 个 key

**库房管理对比分析（库房管理.md）**
- 下载并逐张查看 16 张 kqyun.com 截图（5.1.1~5.9.5）
- 输出布局分析（统一布局模式、卡片仪表盘、左树+右表、弹窗表单等）
- 输出功能完成度对比（本系统 ~65%）+ 本系统优势（FIFO、加权成本、价格偏差预警、API）
- 输出可参考布局建议（7 项）

### 关键决策
| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| 收费大类汇总数据来源 | invoice_items.amount（开票金额）而非 invoice_payments.amount（收款金额） | 一个 payment 覆盖整张 invoice，无法按项目拆分；开票金额按项目自然分组 |
| refunds join 路径 | 直接 refunds.patient_id 而非 refunds→invoices→appointments→patients | refunds 表有直接 patient_id FK，绕道 appointments 多余且脆弱（appointment 可能为 null） |
| 未收款报表筛选条件 | payment_status IN (unpaid, partial, overdue) | 排除 paid/refunded/written_off，覆盖所有「还有钱没收到」的状态 |
| 员工收费明细 vs 复用 payments tab | 独立 tab + cashier 下拉筛选 | 参考系统是独立维度（员工收费明细），功能语义不同于「按支付方式筛选的收款明细」 |

### AI 推理链（关键决策必填）

决策：refunds 表列名修复
1. 读取 `FinancialDetailReportService::getRefunds()` — 使用了 `refunds.user_id`, `refunds.amount`, `refunds.reason`
2. 读取 `2026_01_17_500007_create_refunds_table.php` — 实际列名: `_who_added`, `refund_amount`, `refund_reason`, 且有直接 `patient_id` FK
3. 发现 join 链 `refunds→invoices→appointments→patients` 是错误的，因为 `refunds` 有直接 `patient_id`
4. 同时发现 `refunds.created_at` 用于展示日期不合适，应使用业务字段 `refund_date`
5. 修复：5 处列名替换 + 去掉 appointments join + 用 refund_date

决策：收费大类汇总的聚合逻辑
1. 读取 `medical_services` 表：有 `category` 字段（migration 2026_01_18 添加）
2. 考虑方案 A：SUM(invoice_payments.amount) 按 category 分 — 但一个 payment 对应整张 invoice（含多类项目），无法拆分
3. 考虑方案 B：SUM(invoice_items.amount) 按 medical_services.category 分，用 payment 日期做时间过滤
4. 方案 B 的含义：「在这个时间段内收到过款的发票里，各大类的账单金额是多少」
5. 选择方案 B：与参考系统「收费大类汇总查询收费单据数与收费金额」语义一致

### 放弃的方案
| 方案描述 | 放弃原因 |
|----------|---------|
| refunds 通过 invoices→appointments→patients 间接关联患者 | refunds 有直接 patient_id FK，间接关联多余且脆弱 |
| 按 payment_amount 分摊到各 category（加权比例法） | 过于复杂，且参考系统的「收费大类」统计的是开票金额非收款金额 |
| 员工收费明细复用 payments tab + 加筛选 | 参考系统是独立维度，操作逻辑不同（按员工查看 vs 按支付方式查看）|

### 遗留问题（下次会话继续）
- [ ] 报表第 2 周：医生「我的绩效」自查视角（doctor-report + Auth::id() 过滤）
- [ ] 报表第 2 周：预约分析补患者来源 & 标签维度
- [ ] 库房高优：收费自动扣库存（ServiceConsumable 联动 StockOutService）
- [ ] 库房高优：供应商字段扩展（phone/email/address/contact/证照）
- [ ] 库房中优：今日库房仪表盘（卡片式，参考 5.1.1 布局）
- [ ] 库房中优：物品批量导入 Excel（参考 5.9.2）
- [ ] 需验证：appointment-analytics-report、revisit-rate-report、treatment-plan-completion-report 的 SQL 和业务逻辑是否正确

### Spec 变更建议
- 文件：intent.md  内容：建议新增 AG-027「收费确认时，关联 ServiceConsumable 的物品必须校验库存充足，不足时阻止收费并提示」
- 文件：intent.md  内容：建议新增 AG-028「出库单确认扣库存后，current_stock 不允许为负数」

---

日期：2026-03-08  任务类型：处方模块重构（续）+ Code Review  关联切片：处方-收费联动

### 本次完成了什么

**步骤3: 患者详情页处方独立 Tab**
- `PatientService::getPatientDetail()` 新增 `$prescriptionsCount`
- `show.blade.php`: 处方 Tab（Invoices 和 Lab Cases 之间）+ 懒加载 + i18n 注入
- `patient_detail.js`: 全套 CRUD/结算/打印 JS（~400 行）
  - `loadPatientPrescriptions()` DataTable 懒加载
  - `createPatientPrescription()` / `editPrescription()` 弹窗 + 动态 items
  - `viewPrescription()` 详情弹窗
  - `settlePrescription()` / `deletePrescription()` 确认框
  - `printPrescription()` 新标签页 PDF
  - services 接口缓存 (`rxServicesCache`)
- `patients/modals/add_prescription.blade.php` 创建/编辑弹窗
- `patients/modals/view_prescription.blade.php` 详情弹窗
- `resources/lang/zh-CN/patient.php` 新增 `prescriptions` key

**步骤4: 处方-收费联动前端（部分，与步骤3合并）**
- 保存并结算按钮（`btn-rx-settle`）
- 结算确认框（`settlePrescription()`）
- 已结算处方隐藏删除/结算按钮

**步骤5: 旧数据迁移**
- `2026_03_08_100003_migrate_legacy_prescriptions_to_items.php`
- 匹配策略: 精确→模糊→null 三级降级
- 补全: patient_id(从appointment)/doctor_id(从_who_added)/prescription_no(自动生成)/status(completed)/prescription_date
- 幂等: 跳过已有 items 的记录
- 安全: 未匹配药物 Log::warning

**步骤6: 打印模板**
- `prescriptions/print.blade.php` 新流程 PDF（items 表格+总金额+备注）

**Spec 更新**
- `intent.md`: AG-021~AG-026（病历模板 + 处方收费联动）
- `state-machines.yaml`: prescription 状态机（pending/filled/completed/discontinued/on_hold）

**Code Review（/code-review）**
- 审查 13 个文件，结果: 严重 3 / 一般 3 / 建议 3
- VIBE-CHECKLIST: 第四关❌（AG-005 + 已结算保护）、第五关⚠️（裸 catch + Blade 查询）

### 关键决策
| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| 处方 Tab 位置 | Invoices 和 Lab Cases 之间 | 处方与收费关联紧密，放在收费旁边符合操作动线 |
| Tab 加载方式 | 懒加载（shown.bs.tab） | 复用 lab_cases 已验证的模式，避免页面初始化时多余请求 |
| Services 数据获取 | 前端缓存 rxServicesCache | 创建/编辑弹窗共用，避免重复 AJAX |
| 旧数据迁移匹配 | 精确→模糊→null 三级降级 | 最大化匹配率，无法匹配的日志记录供人工审查 |
| 弹窗复用 | 创建/编辑共用一个 modal | 通过 rx_edit_id 隐藏字段区分模式，减少 HTML 重复 |

### AI 推理链（关键决策必填）

决策：处方 Tab 位置
1. 读取 show.blade.php: 现有 Tab 顺序为 基本信息→牙位图→预约→病例→影像→收费→技工单→随访
2. 处方与收费高度关联（保存并结算直接生成 Invoice）
3. 考虑方案A: 放在最后（随访后面）→ 操作跳转距离远
4. 考虑方案B: 放在收费和技工单之间 → 从处方 Tab 结算后切到收费 Tab 查看只需右移一格
5. 选择方案B: 符合"开处方→结算→查账单"的操作动线

决策：旧数据迁移策略
1. 读取 prescriptions 表: legacy 记录只有 drug(文本)/qty/directions
2. 新流程需要 prescription_items + medical_service_id + unit_price
3. 考虑方案A: 不迁移，legacy 保持原样 → 两套数据格式长期共存，维护成本高
4. 考虑方案B: 迁移为 items 行 + 三级匹配 → 统一数据格式，匹配不上的留 null 不丢数据
5. 选择方案B: 迁移脚本幂等（跳过已有 items）、可安全重跑、未匹配记录 Log::warning

### 放弃的方案
| 方案描述 | 放弃原因 |
|----------|---------|
| 处方 Tab 放最后 | 与收费联动操作距离远，不符合动线 |
| 创建/编辑用两个独立 modal | HTML 重复量大，维护成本翻倍 |
| 旧数据不迁移 | 两套格式长期共存增加维护和查询复杂度 |
| Services 每次打开弹窗重新请求 | 数据变化频率低，缓存即可，减少不必要请求 |

### 遗留问题（下次会话继续）
- [x] **严重** print.blade.php:44 金额用 `*` 运算，违反 AG-005，改为 bcmul/bcadd
- [x] **严重** PrescriptionService::updatePrescription() 未阻止已结算处方修改 items
- [x] **严重** patient_detail.js addRxItemRow() svc.name 未 HTML 转义（XSS）
- [x] **一般** PrescriptionService 四处裸 catch 返回 $e->getMessage()，需 Log::error + 通用消息
- [x] **一般** PrescriptionController::patientPrescriptions() 非 AJAX 无返回值
- [x] **一般** add_prescription.blade.php Blade 内 DB 查询医生列表，应从 Service 传入
- [x] **建议** 状态标签 map 重复 2 次，提取私有方法
- [x] **建议** createPrescription/saveAndSettle 前半段重复，提取公共方法
- [x] **待补充** AG-023/024/025/026 无测试覆盖 → 14 tests, 35 assertions 全通过

---

日期：2026-03-08  任务类型：处方模块重构  关联切片：处方-收费联动

### 本次完成了什么

**功能一：病历另存为模板（已完成）**
- edit.blade.php 弹窗优化（编码→可选快捷码 + 草稿校验）
- JS: 草稿检查(AG-021) + 编码可选
- 后端: code 可选 + 自动生成 tpl_N + 权限强制 personal(AG-022) + 并发 retry
- 迁移: code 列唯一约束改为 (code, deleted_at) 复合索引
- 权限: store/search/incrementUsage 放开为 manage-medical-cases
- i18n: zh-CN/en 各增 4 个 key
- Vibe-Check + Code Review 通过（3 严重 + 3 一般已修复）

**功能二：处方模块重构（已完成 6/6 步）**
- 步骤1 ✅ 迁移: medical_services.is_prescription + prescription_items.medical_service_id/unit_price + prescriptions.invoice_id
- 步骤1 ✅ Model: MedicalService(scopePrescription) + PrescriptionItem(medicalService/amount) + Prescription(invoice/is_deletable/total_amount)
- 步骤2 ✅ PrescriptionService 全量重写: createPrescription/saveAndSettle/settlePrescription/deletePrescription(AG-023)/createItems(AG-025/026)
- 步骤2 ✅ PrescriptionController 全量重写: 新流程+legacy 兼容
- 步骤2 ✅ 路由: 4 条新增 (patient/{id}, services, pending/{id}, {id}/settle)
- 步骤2 ✅ i18n: zh-CN/en 各增 16 个 key
- 步骤3 ✅ 患者详情页处方独立 Tab: DataTable+懒加载+创建/查看/编辑弹窗+结算/删除/打印操作
- 步骤4 ✅ 处方-收费联动前端: 保存并结算按钮、结算确认框、状态徽章
- 步骤5 ✅ 旧数据迁移: drug_name→medical_service_id 精确+模糊匹配，补全 patient_id/doctor_id/prescription_no/status
- 步骤6 ✅ 打印模板: prescriptions/print.blade.php 新流程打印（items 表格+总金额）

**Spec 更新（已完成）**
- intent.md: 新增 AG-021~AG-026（病历模板 + 处方收费联动）
- state-machines.yaml: 新增 prescription 状态机（pending→filled→completed）

### 关键决策
| 决策内容 | 选择方案 | 原因摘要 |
|----------|---------|---------|
| 处方价格来源 | 后端从 medical_services 取价(AG-025) | 不信任前端传入单价，防篡改 |
| Invoice 关联方式 | prescriptions.invoice_id FK + set null on delete | 删 Invoice 后 invoice_id 变 null，处方变为可删除(AG-023) |
| 处方结算方式 | 调用 InvoiceService::createBillingInvoice() | 复用现有收费逻辑，含折扣审批/事务/金额计算 |
| Legacy 兼容 | 保留旧方法 createPrescriptions/getPrintDataByAppointment | 不破坏现有 appointment 维度的处方功能 |
| 金额计算 | bcmul/bcadd (AG-005) | 处方金额涉及财务，必须高精度 |
| 旧数据匹配策略 | 精确匹配→模糊匹配→留 null | 三级降级，未匹配的记录 Log::warning 供人工审查 |
| 处方 Tab 位置 | Invoices 和 Lab Cases 之间 | 处方与收费关联紧密，放在收费旁边符合操作动线 |

### AI 推理链（关键决策必填）

决策：Invoice 关联方式
1. 读取 spec: AG-023 要求已关联 Invoice 的处方不可删除，删 Invoice 后可删处方
2. 考虑方案A: invoices.prescription_id → 但一个 Invoice 可能来自多个处方
3. 考虑方案B: prescriptions.invoice_id FK → 多个处方可指向同一 Invoice
4. FK onDelete 选择: set null → 删除 Invoice 时自动清除 invoice_id，处方变为 is_deletable=true
5. 选择方案B + set null: 完美匹配 AG-023 语义

决策：保存并结算的实现
1. 读取 InvoiceService::createBillingInvoice() — 已有完整的事务+折扣审批+金额计算
2. 考虑方案A: 在 PrescriptionService 中直接创建 Invoice → 重复代码，绕过折扣审批
3. 考虑方案B: 调用 createBillingInvoice() → 复用逻辑，自动处理 BR-035
4. 选择方案B: prescription items 转换为 invoice items 格式后传入，billingMode='front_desk'

决策：旧数据迁移匹配策略
1. 读取现有数据结构: legacy 处方只有 drug(文本)/qty/directions，无 medical_service_id
2. 考虑方案A: 仅精确匹配 → 匹配率可能低，大量数据遗漏
3. 考虑方案B: 精确→模糊→留 null 三级降级 → 最大化匹配率，无法匹配的记录日志
4. 选择方案B: 模糊匹配同时自动标记 is_prescription=true，扩大可选范围

### 遗留问题（下次会话继续）
- (全部完成，无遗留)

### Spec 变更建议
- (已执行) intent.md: AG-021~AG-026
- (已执行) state-machines.yaml: prescription 状态机

---

## 推理链示例（口腔医疗场景）

```
---
日期：2026-03-07  任务类型：收费模块开发  关联切片：切片 B
使用的 Prompt 版本：generate-service/v1.0

### 本次完成了什么
- InvoiceService 折扣计算逻辑（四级优先级）
- 折扣审批流程（BR-035）
- 对应 PHPUnit 测试

### 关键决策
| 决策内容               | 选择方案                 | 原因摘要                          |
|-----------------------|-------------------------|-----------------------------------|
| 折扣计算顺序           | 会员→项目→整单→优惠券     | intent.md 明确定义四级优先级        |
| 审批阈值存储           | rule-engine.md Rule Key  | 避免硬编码 500 元，便于后续调整     |
| 金额精度方案           | bcmul() + decimal:2     | 浮点运算在折扣叠加时会产生精度误差   |

### AI 推理链
决策：折扣金额精度方案
1. 我读取了 CLAUDE.md：「所有金额字段使用 decimal:2 cast」
2. 我注意到 Invoice 模型对 subtotal/discount_amount 等字段使用了 decimal:2 cast
3. 我考虑了「PHP 原生浮点运算」和「bcmath 高精度运算」
4. PHP 原生浮点的问题：0.1 + 0.2 !== 0.3，多级折扣叠加后误差累积
5. 选择 bcmath，因为医疗收费场景不允许金额误差

### 放弃的方案
| 方案描述               | 放弃原因                                |
|-----------------------|-----------------------------------------|
| PHP 原生浮点运算       | 折扣叠加后精度累积误差不可接受             |
| 折扣阈值硬编码 500     | rule-engine.md 要求所有业务阈值通过 Key 引用 |

### 遗留问题
- [ ] 优惠券与会员折扣是否可以同时使用？需确认 intent.md
- [ ] 退款部分折扣回退逻辑待实现

### Spec 变更建议
- 文件：intent.md  内容：补充「优惠券与会员折扣叠加规则」

### Prompt 效果反馈
版本：generate-service/v1.0  评价：好用
具体：主动识别了 bcmath 需求，自动引用了 rule-engine.md 的 Key
---
```

---

## 历史摘要区（超过 10 条后压缩至此）

_（暂无历史记录）_
