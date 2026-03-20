# intent.md — 口腔医疗管理系统业务意图（最高优先级）

## 系统核心意图
为口腔诊所提供一站式管理系统，覆盖从患者预约、就诊、治疗到收费的全流程。
系统面向五种角色：医生、护士、前台、管理员、药房，各司其职。

---

## Anti-Goals（系统不允许做的事情）

### 收费与财务
- **AG-001**: 折扣 > 500 元必须触发管理员审批流程（BR-035），不允许绕过
- **AG-002**: 退款金额不允许超过该账单已付金额
- **AG-003**: 退款 > 100 元必须经过审批
- **AG-004**: 会员余额不允许为负数
- **AG-005**: 金额计算不允许使用浮点运算，必须使用 bcmath
- **AG-006**: 折扣优先级必须严格遵循：会员折扣→项目折扣→整单折扣→优惠券

### 病历合规
- **AG-007**: 病历锁定后不允许直接修改，必须走 Amendment 审批流程
- **AG-008**: Amendment 审批拒绝后，不允许自动应用修改内容
- **AG-009**: 病历版本号（version_number）不允许手动修改，只在 Amendment 审批通过后自增

### 预约与候诊
- **AG-010**: 同一时段同一椅位不允许重复预约；同一时段同一医生预约数不允许超过班次 max_patients
- **AG-011**: 候诊队列不允许从 waiting 直接跳到 completed（必须经过 called → in_treatment）
- **AG-012**: 已取消的预约不允许重新激活为 completed 状态

### 技工单
- **AG-013**: 技工单状态不允许逆向流转（如 completed → sent）
- **AG-014**: 返工（rework）必须记录返工原因（rework_reason 不可为空）

### 数据安全
- **AG-015**: 患者 NIN（身份证号）不允许出现在任何日志输出中
- **AG-016**: 候诊大屏显示必须使用脱敏姓名（masked_patient_name）
- **AG-017**: 患者合并后，原记录只能标记为 merged，不允许物理删除
- **AG-018**: 会员密码（member_password）不允许明文存储

### 病历模板
- **AG-021**: 草稿状态（draft）的病历不允许另存为模板，必须先完成病历后再保存
- **AG-022**: 医生另存为模板时，scope 强制为 personal（个人模板），不允许直接创建全局模板

### 处方与收费联动
- **AG-023**: 已关联 Invoice 的处方不允许删除，必须先删除账单后再删除处方
- **AG-024**: 处方结算操作必须幂等——已结算的处方再次调用结算时返回提示，不允许重复创建 Invoice
- **AG-025**: 处方项目单价必须从 medical_services 表后端获取，不允许信任前端传入的价格
- **AG-026**: 处方项目数量最小为 1，不允许 0 或负数

### 账号与认证
- **AG-027**: 离职状态（resigned）的用户不允许通过任何渠道（Web/API）登录，状态变更为离职时必须清除该用户所有 Sanctum Token
- **AG-028**: 用户名（username）必须全局唯一，自动生成时重复须追加数字后缀（zhangwei, zhangwei1, zhangwei2），不允许覆盖
- **AG-029**: 离职员工不允许被分配为新预约的医生、不允许出现在医生选择下拉列表中
- **AG-030**: 登录失败提示不允许区分"用户不存在"和"密码错误"，统一返回"用户名或密码错误"
- **AG-031**: 员工状态从"离职"恢复为"在职"时，必须强制重置密码

### 排班管理
- **AG-032**: 医生角色只允许查看自己的排班，不允许编辑其他医生的排班
- **AG-033**: 医生不允许删除当天及之前的排班，仅管理员可操作历史排班
- **AG-034**: 修改班次模板时间时，必须提示受影响的排班数量和关联预约数，需二次确认
- **AG-035**: 被排班（`doctor_schedules.shift_id`）引用的班次不允许删除（FK RESTRICT）；`appointments.shift_id` 采用 `nullOnDelete`——班次删除时预约的 shift_id 置 null，不阻止删除
- **AG-036**: 有关联预约（非取消/爽约状态）的排班不允许直接删除
- **AG-037**: max_patients 限制必须在预约创建时做数据库级原子检查，不允许仅依赖前端校验
- **AG-038**: `work_status = 'rest'` 的班次不得产生可预约时段；"有排班"的判定必须同时要求 `shift.work_status = 'on_duty'`（枚举值：`on_duty` / `rest`）
- **AG-039**: `appointments.shift_id` 一旦写入不得被级联修改；班次时间修改不得追溯更新已有预约的 `shift_id`（保留历史快照语义）
- **AG-040**: `clinic.slot_interval` 必须 > 0，`clinic.start_time` 必须早于 `clinic.end_time`；系统设置保存接口必须做服务端校验，不允许写入非法值
- **AG-041**: max_patients 检查必须在数据库事务中用悲观锁（`SELECT FOR UPDATE`）完成；Web Controller 和 API Controller 必须共用同一 `AppointmentService` 方法，不允许各自实现校验逻辑
- **AG-042**: 修改 `clinic.require_schedule_for_booking` 开关只允许超级管理员和管理员操作（`can:manage-settings`），不允许医生或前台修改

### 报表与导出
- **AG-043**: 所有财务报表（财务日历、现金汇总、财务明细、发票报表）必须使用统一数据源和计算口径；收入取 `invoice_payments`（实收），支出取 `expense_payments`，退款取 `refunds`，三者不允许混淆合并
- **AG-044**: 报表日期范围查询最大跨度不允许超过 12 个月；超出时后端拒绝并返回提示
- **AG-045**: 报表 Excel 导出不允许包含患者 NIN（身份证号），手机号必须脱敏为 `138****1234` 格式
- **AG-046**: Tab 合并后，原有独立报表的 URL 必须保留 301 重定向到新合并页面对应 Tab，不允许直接删除旧路由
- **AG-047**: Tab 内容必须懒加载（切换时才初始化），不允许页面加载时同时初始化所有 Tab 的 DataTable/Chart

### 库存管理
- **AG-048**: 库存扣减（FIFO 批次）必须在数据库事务内完成，批次扣减使用悲观锁（`SELECT FOR UPDATE`）防止并发超扣
- **AG-049**: 发票创建的库存扣减必须幂等——同一 `invoice_id` 不得重复生成出库单；重复调用时返回提示而非重复扣减
- **AG-050**: 发票删除回滚库存时，必须在同一事务内回滚出库单 + 恢复批次数量 + 更新 `current_stock`，不允许部分成功
- **AG-051**: 库存不足时允许收费但必须记录警告日志（含物品名、缺口数量），出库单标注 `stock_insufficient=true`
- **AG-052**: 申领单提交后（`pending_approval`）不可编辑，只能审批通过或驳回
- **AG-053**: 审批人不得与申领人为同一 `user_id`（`approved_by != _who_added`）
- **AG-054**: 单次申领数量上限由 rule-engine 配置（Rule Key: `inventory.max_requisition_qty`，默认 100），超出时后端拒绝
- **AG-055**: 报损和退货必须经过审批（`pending_approval → confirmed`），操作人和审批人不得相同（复用 AG-053）
- **AG-056**: 报损/退货数量不得超过该物品当前可用库存（`current_stock`）
- **AG-057**: `out_type=supplier_return` 时 `supplier_id` 为必填，不允许为 null
- **AG-058**: 同一分类同一天只允许一张未完成的盘点单，防止重复盘点导致数据混乱
- **AG-059**: 盘点单创建时锁定 `system_qty` 快照，不随后续出入库变动；盘点结果基于快照计算差异
- **AG-060**: 盘盈/盘亏偏差超过 `system_qty` 的 50% 时需要额外审批确认（Rule Key: `inventory.check_deviation_threshold`，默认 0.5）
- **AG-061**: 有关联入库单的供应商不可删除（应用层检查 `stock_ins.supplier_id`）
- **AG-062**: Excel 导入必须验证 `item_code` 唯一性，重复行跳过并记录到错误报告返回给用户
- **AG-063**: `current_stock` 不得为负数，扣减前必须检查；应用层悲观锁 + 数据库 `CHECK(current_stock >= 0)` 双重保障
- **AG-064**: `status=confirmed` 或 `status=cancelled` 的入库/出库单不可编辑或删除
- **AG-065**: 库存金额计算（`average_cost`、`amount`）必须使用 `bcmath`，不使用浮点运算（对齐 AG-005）
- **AG-066**: 同一分类同一天创建盘点单必须在数据库层做唯一约束（`category_id + check_date`，where `status IN (draft, in_progress)`），不允许仅靠应用层防重复——并发创建时唯一索引是最后防线
- **AG-067**: 盘点单确认（`confirm()`）时后端必须重新计算各物品偏差率，不允许信任前端传入的"已二次确认"标志；偏差超过 `inventory.check_deviation_threshold` 时必须在后端再次拦截
- **AG-068**: Excel 导入文件大小不允许超过 10MB，行数不允许超过 5000 行；超出时后端拒绝并返回提示，不允许静默处理超大文件

### 通用
- **AG-019**: 所有查询必须处理软删除（`whereNull('deleted_at')` 或 SoftDeletes trait）
- **AG-020**: 业务阈值不允许硬编码，必须通过 rule-engine.md 的 Rule Key 引用或系统设置

---

## 数据敏感级别

| 字段             | 级别 | 处理方式                                        |
|-----------------|------|------------------------------------------------|
| patient.nin     | 高敏 | 加密存储（EncryptsNin trait），日志禁止输出，报表/导出禁止出现（AG-015/045） |
| member_password | 高敏 | Hash 存储，不允许明文                             |
| 病历内容         | 中敏 | 仅授权医生可查看，审计追踪所有访问                  |
| patient.phone   | 中敏 | 候诊大屏脱敏显示，报表/导出脱敏为 `138****1234`（AG-045） |
| patient.name    | 中敏 | 候诊大屏使用 masked_patient_name                  |
| 诊所总收入/利润   | 中敏 | 仅 `view-reports` 权限可见                        |
| 医生个人收入     | 中敏 | 报表可见，导出不含其他医生薪资                      |
| 预约信息         | 低敏 | 正常权限控制                                      |
| appointments.shift_id | 低敏 | 班次快照引用，不含患者隐私；写入后不级联更新（AG-039） |
| 服务项目         | 低敏 | 正常权限控制                                      |
| users.username  | 低敏 | 唯一约束，正常权限控制                              |
| users.status    | 低敏 | 枚举值（active/resigned），认证全链路校验            |
| shifts.*        | 低敏 | 正常权限控制                                        |
| doctor_schedules.shift_id | 低敏 | 正常权限控制                              |
| 库存数量/成本    | 商业敏感 | 按角色权限控制可见性（`manage-inventory` / `operate-inventory`） |
| 盘点差异记录     | 商业敏感 | 需审计追溯（记录 system_qty vs actual_qty）           |
| 供应商联系方式   | 低敏 | 正常存储，列表显示                                    |
| 物品批次号/有效期 | 低敏 | 正常存储                                              |

---

## 目标用户与角色

| 角色                | 主要职责                                  | 关键权限                          |
|--------------------|------------------------------------------|-----------------------------------|
| Super Administrator | 系统全局管理                              | 所有权限                           |
| Administrator       | 诊所日常管理                              | 审批折扣/退款、用户管理            |
| Doctor              | 看诊、开处方、制定治疗计划                 | 病历读写、技工单管理               |
| Nurse               | 协助就诊、生命体征记录                    | 候诊管理、病历查看                 |
| Receptionist        | 前台接待、预约管理、收费                   | 预约管理、收费、患者管理           |

---

## 登录方式优化 + 员工生命周期管理

### 业务意图
将登录方式从"仅邮箱"扩展为"用户名或邮箱"，同时引入员工状态管理，支持离职处理。

### 目标用户
所有角色（Doctor / Nurse / Receptionist / Administrator / Super Administrator）

### 正常流程

#### 登录流程
1. 用户在登录页输入用户名（或邮箱）+ 密码
2. 系统自动识别输入类型（含 `@` 为邮箱，否则为用户名）
3. 验证凭据 + 检查 `status === 'active'`
4. 登录成功 → 跳转首页；失败 → 统一提示"用户名或密码错误"（AG-030）

#### 用户创建流程
1. 管理员创建用户时，填写 username（必填，唯一）
2. 格式：姓名拼音简称（如 `zhangwei`）
3. 系统校验唯一性，冲突时提示管理员修改（AG-028）

#### 存量迁移流程
1. Migration 自动为现有用户生成 username = 姓名拼音简称
2. 重复时追加数字后缀（zhangwei, zhangwei1, zhangwei2）（AG-028）
3. 所有现有用户 status 默认设为 `active`
4. 迁移后管理员可手动调整 username

#### 员工离职流程
1. 管理员进入用户管理 → 选择员工 → 修改状态为"离职"（resigned）
2. 系统自动清除该用户所有 Sanctum Token（AG-027）
3. 该员工立即无法登录（Web + API）
4. 关联数据保留：病历、预约历史、账单等不受影响
5. 该员工从医生选择列表、预约分配列表中移除（AG-029）
6. 历史数据中显示"医生姓名（已离职）"

#### 员工复职流程
1. 管理员将离职员工状态改回"在职"（active）
2. 系统强制重置密码，管理员设定新密码后通知员工（AG-031）

### 涉及的实体变更

| 实体 | 变更类型 | 说明 |
|------|---------|------|
| User | 新增字段 | `username` (string, unique) — 登录用户名 |
| User | 新增字段 | `status` (enum: active/resigned, default: active) — 员工状态 |
| User | 修改逻辑 | 登录支持 username，认证检查 status |

### 员工状态机

```
active ──(管理员操作: 离职)──→ resigned
  ↑                              │
  └──(管理员操作: 复职+重置密码)──┘
```

- `active → resigned`: 清除所有 Sanctum Token，从医生选择列表移除
- `resigned → active`: 强制重置密码

### 权限矩阵

| 操作 | Super Admin | Admin | Doctor | Nurse | Receptionist |
|------|:-----------:|:-----:|:------:|:-----:|:------------:|
| 创建用户（设定username） | ✅ | ✅ | ❌ | ❌ | ❌ |
| 修改员工状态 | ✅ | ✅ | ❌ | ❌ | ❌ |
| 修改自己的密码 | ✅ | ✅ | ✅ | ✅ | ✅ |

### 涉及修改的文件

| 文件 | 修改内容 |
|------|---------|
| `app/User.php` | 新增 username/status 字段，scopeActive 方法 |
| `app/Http/Controllers/Auth/LoginController.php` | 支持 username 登录，status 检查 |
| `app/Http/Controllers/Api/V1/AuthController.php` | API 登录支持 username，status 检查 |
| `resources/views/auth/login.blade.php` | 输入框改为"用户名/邮箱" |
| 用户管理 Controller/View | 新增 username、status 字段的 CRUD |
| 医生选择下拉组件 | 过滤 status !== 'active' 的医生 |
| Migration | 新增 username、status 字段；存量数据拼音生成 |

---

## 医生排班增强

### 业务意图
重构排班系统：引入「班次」模板 + 月度排班网格 + 复制排班，支持午休时段、冲突检测、max_patients 实际限制。无排班时的预约行为通过系统设置控制（可配置为 fallback 默认营业时间或禁止预约）。

### 目标用户

| 角色 | 场景 |
|------|------|
| Administrator | 管理班次模板、为所有医生排班、复制排班、导出排班表 |
| Doctor | 查看自己排班、编辑自己未来排班 |
| Nurse | 查看所有排班（只读） |
| Receptionist | 预约时受排班约束（无排班不可预约、max_patients 限制） |

### 正常流程

#### 班次管理
1. 管理员进入「班次设置」弹窗
2. 添加班次：名称（如上午班）、上班时间、下班时间、工作状态（上班/休息）、颜色标识、max_patients
3. 支持排序（上下移动）、编辑、删除（被引用时禁止删除 AG-035）
4. 班次区分「上班」和「休息」两种工作状态
5. 通过多个班次组合实现午休：如「上午班 08:00-12:00」+「下午班 13:30-18:00」

#### 排班操作（月度网格）
1. 管理员进入排班页面，显示月度网格视图（行=员工，列=1~31日）
2. 顶部显示月份选择器、班次按钮区（彩色标签）、快速排班工具栏
3. 点击某个员工某天的格子 → 弹出班次选择
4. 或从顶部班次按钮区域拖拽班次标签到格子
5. 系统自动检测冲突：同医生同天不允许时间段重叠
6. 格子中显示彩色班次标签（支持一天多个班次）
7. 医生角色进入时，仅显示自己的排班行，可编辑未来日期（AG-032, AG-033）
8. 护士角色进入时，显示所有排班，只读模式

#### 复制排班
1. 快速排班工具栏：「使用 [源周] 所在周的排班表，复制到 [目标周]」→ 确定
2. 「复制上月排班」按钮 → 将上月排班复制到当前月
3. 复制时检查目标已有排班 → 提示「目标已有 X 条排班，是否覆盖？」
4. 确认后批量生成，记录操作人

#### 循环排班（保留，增强联动）
1. 创建排班时可选「启用重复」→ 选择模式（每日/每周/每月）+ 结束日期
2. 生成的排班共享 `recurring_group_id`
3. 编辑循环排班时可选：「仅修改此条」/「修改此条及之后所有」/「修改全部」

#### 预约联动
1. 前台创建预约 → 选择医生 + 日期
2. 系统查该医生该天的排班：
   - **有排班** → 根据班次的上班时间/下班时间生成可选时段
   - **多个班次** → 合并所有「上班」状态班次的时段
   - **无排班** → 行为由系统设置 `clinic.require_schedule_for_booking` 决定：
     - `false`（默认）→ 使用系统营业时间（`clinic.start_time` ~ `clinic.end_time`）作为 fallback，前端显示提示「该医生未排班，使用默认时间」
     - `true`（严格模式）→ 显示「该医生当天未排班，不可预约」
3. 每个时段检查已预约数 vs 班次 max_patients → 满则标记不可选（AG-037）
4. 预约创建时后端再次验证排班存在（或 fallback 允许）+ max_patients 未满（数据库级原子检查）

> **过渡策略**：系统上线初期 `clinic.require_schedule_for_booking = false`，排班数据补全后由管理员在「诊所设置」页切换为 `true`。配合 `clinic.hide_off_duty_doctors` 设置使用。

#### 删除排班保护
1. 删除排班前，系统检查该天该医生是否有关联预约（非取消/爽约状态）
2. 有关联预约 → 弹出警告，列出受影响预约，禁止直接删除（AG-036）
3. 必须先取消或改期相关预约后才能删除排班

### 异常流程

| 场景 | 处理方式 |
|------|---------|
| 修改班次模板时间 | 提示受影响排班数 + 关联预约数，需二次确认（AG-034） |
| 删除被引用的班次 | 阻止删除，提示「该班次已被 X 条排班使用」（AG-035） |
| 并发预约竞争 max_patients | 数据库级原子检查，后到者返回「该时段已满」（AG-037） |
| 复制排班目标已有数据 | 提示是否覆盖，确认后替换 |
| 离职医生 | 不出现在排班医生选择列表中（AG-029） |
| 无排班预约（宽松模式） | fallback 系统营业时间，前端提示「未排班，使用默认时间」 |
| 无排班预约（严格模式） | 禁止预约，提示「该医生当天未排班」（AG-038；`clinic.require_schedule_for_booking=true`） |
| 时间在班次外 | 禁止预约，提示「所选时间不在排班范围内」（AG-038；有 on_duty 排班但时间不在任何班次窗口内） |
| 仅有 rest 班次 | 等同于无有效排班，按 `require_schedule_for_booking` 走分支 |

### 涉及的实体变更

| 实体 | 变更类型 | 说明 |
|------|---------|------|
| Shift | 新增表 | `id`, `name`, `start_time`, `end_time`, `work_status`(上班/休息), `color`, `sort_order`, `max_patients`, `timestamps`, `deleted_at` |
| DoctorSchedule | 字段置空 | `start_time`, `end_time`, `max_patients` 改为 `nullable`（向后兼容保留，业务逻辑已改由 Shift 承载；`getEffectiveStartTime()` 等方法提供 fallback） |
| DoctorSchedule | 新增字段 | `shift_id` (FK → shifts) |
| DoctorSchedule | 新增字段 | `recurring_group_id` (nullable, 循环排班组标识) |
| Appointment | 新增字段 | `shift_id` (nullable FK → shifts.id, `nullOnDelete`) — 记录预约创建时匹配的班次；班次删除时置 null；写入后改期/更新预约均不覆盖此字段（历史快照语义） |

### 权限矩阵

| 操作 | Super Admin | Admin | Doctor | Nurse | Receptionist |
|------|:-----------:|:-----:|:------:|:-----:|:------------:|
| 班次模板 CRUD | ✅ | ✅ | ❌ | ❌ | ❌ |
| 查看所有排班 | ✅ | ✅ | ❌ | ✅(只读) | ❌ |
| 查看自己排班 | — | — | ✅ | ✅ | ❌ |
| 创建/编辑排班 | ✅ | ✅ | ✅(仅自己) | ❌ | ❌ |
| 删除排班 | ✅ | ✅ | ✅(仅自己+未来) | ❌ | ❌ |
| 复制排班（周/月） | ✅ | ✅ | ❌ | ❌ | ❌ |
| 导出排班表 | ✅ | ✅ | ❌ | ❌ | ❌ |

### 权限 Slug

| Slug | 说明 | 角色 |
|------|------|------|
| `manage-shifts` | 班次模板管理 | Super Admin, Admin |
| `manage-schedules` | 排班管理-全部（已存在） | Super Admin, Admin |
| `view-own-schedule` | 查看自己排班 | Doctor, Nurse |
| `edit-own-schedule` | 编辑自己排班 | Doctor |
| `view-all-schedules` | 查看所有排班（只读） | Nurse |

### Rule Key 变更

| Rule Key | 默认值 | 状态 | 说明 |
|----------|--------|------|------|
| `clinic.require_schedule_for_booking` | `false` | ✅ 已实现 | `false`=无排班时 fallback 系统营业时间；`true`=无排班禁止预约（在诊所设置页管理） |
| `schedule.max_patients_upper_limit` | 50 | ⏳ 已 seed，未实现 | 班次 max_patients 允许设置的上限，目前无 UI 管理界面，业务层未使用 |
| `schedule.copy_max_range_months` | 3 | ⏳ 已 seed，未实现 | 复制排班最大跨度（月），目前无 UI 管理界面，业务层未使用 |

### 实施路径

```
Phase 1: 班次管理（shifts 表 + 班次设置弹窗 CRUD）
Phase 2: 月度排班网格（替换 DataTables+FullCalendar）
         ├─ 点击/拖拽分配班次
         ├─ 冲突检测
         └─ 删除排班时检查关联预约
Phase 3: 复制排班（周/月）+ 循环排班联动修改
Phase 4: 预约联动增强
         ├─ 无排班行为由 schedule.require_schedule_for_booking 控制
         ├─ max_patients 实际限制（数据库级原子检查）
         └─ 班次时间 → 预约时段生成
```

### 涉及修改的文件

| 文件 | 修改内容 |
|------|---------|
| Migration（新） | 创建 `shifts` 表 |
| Migration（新） | 修改 `doctor_schedules` 表：移除 start_time/end_time/max_patients，新增 shift_id/recurring_group_id |
| `App/Shift.php`（新） | Shift Model |
| `App/DoctorSchedule.php` | 新增 shift() 关联，移除旧字段 |
| `App/Http/Controllers/ShiftController.php`（新） | 班次 CRUD |
| `App/Http/Controllers/DoctorScheduleController.php` | 重构为月度网格 + 复制排班 |
| `App/Services/DoctorScheduleService.php` | 重构：冲突检测、复制、循环联动 |
| `App/Services/AppointmentService.php` | getDoctorTimeSlots 改为基于 Shift，fallback 行为由 `schedule.require_schedule_for_booking` 控制，加 max_patients 检查 |
| `resources/views/doctor_schedules/index.blade.php` | 重构为月度网格视图 |
| `resources/views/doctor_schedules/create.blade.php` | 改为班次选择弹窗 |
| `public/include_js/doctor_schedule_grid.js`（新） | 月度网格交互（点击/拖拽/复制） |
| `public/css/doctor-schedule-grid.css`（新） | 月度网格样式 |
| `public/include_js/appointment_drawer.js` | 适配无排班不可预约 + max_patients 限制 |
| `resources/lang/zh-CN/shifts.php`（新） | 班次翻译 |
| `resources/lang/en/shifts.php`（新） | 班次翻译 |
| `resources/lang/zh-CN/doctor_schedules.php` | 新增网格/复制相关翻译 |
| `resources/lang/en/doctor_schedules.php` | 新增网格/复制相关翻译 |
| `routes/web.php` | 新增 shifts 路由 |
| `database/seeders/PermissionsSeeder.php` | 新增权限 slug |

---

## 报表系统改造

### 业务意图
改造现有报表体系：将 14 个分散报表按业务维度合并为 Tab 分组，新增财务日历、现金汇总、财务明细、技工单统计 4 类报表，升级图表交互为 Radio Button 切换模式，统一拆分内联 CSS 到独立文件。参考口腔云（kqyun.com）报表体系的布局优势，同时保留本系统在经营驾驶舱、治疗计划转化、复诊率分析、月度环比方面的差异化优势。

### 目标用户

| 角色 | 场景 |
|------|------|
| Super Administrator | 查看所有报表、导出 Excel |
| Administrator | 查看所有报表、导出 Excel |
| Doctor / Nurse / Receptionist | 无报表权限（不变，继续使用现有 `view-reports`） |

### 改造范围与正常流程

#### 第一批：Tab 合并 + 财务日历 + CSS 规范化

**A. Tab 合并（4 组）**

将相近含义的报表合并到同一页面，通过顶部 Tab 切换子视图：

| 合并后页面 | 原页面 | Tab 结构 |
|-----------|-------|---------|
| 医生报表 `/doctor-report` | Doctor Performance + Doctor Workload | 收费统计 \| 工作量统计 |
| 收费报表 `/billing-report` | Invoice Payments + Procedures Income | 收费明细 \| 项目收入 |
| 患者报表 `/patient-report` | Patient Source + Patient Demographics | 来源分析 \| 人口统计 |
| 欠费报表 `/debtors-report` | Debtors Report（扩展） | 欠费患者 \| 欠费账单 \| 欠费变动 |

- 旧路由（`/doctor-performance-report`、`/doctor-workload-report` 等）保留 301 重定向到新页面对应 Tab（AG-046）
- Tab 内容懒加载：切换 Tab 时才初始化 DataTable/Chart，避免首次加载过重（AG-047）
- 合并后侧边栏菜单项从 14 → 11

**B. 新增财务日历**

1. 管理员进入「财务日历」页面
2. 显示月历视图（FullCalendar month 模式）
3. 每个日期格显示：绿色●收入金额（`invoice_payments.SUM(amount)`）、红色●支出金额（`expense_payments.SUM(amount)`）
4. 右上角显示：月收入合计 / 月支出合计
5. 点击某个日期 → 弹窗显示当日收支明细列表（收入项：患者姓名+金额+支付方式；支出项：类目+金额）
6. 月份导航：上月/下月箭头
7. 数据源统一遵循 AG-043

**C. 内联 CSS 拆分**

将以下 9 个报表视图中的 `<style>` 块拆分到独立 CSS 文件，遵循 CLAUDE.md 的文件分离规范：

| 视图 | 拆分到 |
|------|-------|
| `business_cockpit.blade.php` | `public/css/business-cockpit.css` |
| `appointment_analytics_report.blade.php` | `public/css/appointment-analytics.css` |
| `revisit_rate_report.blade.php` | `public/css/revisit-rate.css` |
| `patient_source_report.blade.php` | `public/css/patient-source.css` |
| `patient_demographics_report.blade.php` | `public/css/patient-demographics.css` |
| `doctor_workload_report.blade.php` | `public/css/doctor-workload.css` |
| `treatment_plan_completion_report.blade.php` | `public/css/treatment-plan-completion.css` |
| `quotation_conversion_report.blade.php` | `public/css/quotation-conversion.css` |
| `monthly_business_summary_report.blade.php` | `public/css/monthly-business-summary.css` |

#### 第二批：新增报表 + 交互升级

**D. 新增现金汇总报表**

1. 管理员进入「现金汇总」页面
2. 顶部 4 个 Tab：按支付方式汇总 | 按日期汇总 | 按医生汇总 | 按收费大类汇总
3. 每个 Tab 内一个 DataTable，底部合计行
4. 日期范围筛选 + Excel 导出
5. 数据源：`invoice_payments`（AG-043）
6. 支付方式为 NULL 的历史数据归入「其他」类别

**E. 新增技工单统计报表**

1. 管理员进入「技工单统计」页面
2. 顶部汇总卡片（4 列）：总技工单数 | 进行中 | 已完成 | 返工率
3. 图表区域：
   - 左侧：月度技工单趋势折线图
   - 右侧：加工所分布饼图
4. 表格：加工所排名（加工所名称、技工单数、均价、平均天数、返工率）
5. 日期范围筛选 + Excel 导出
6. 数据源：`lab_cases` + `lab_case_items`

**F. 新增财务明细报表**

1. 管理员进入「财务明细」页面
2. 顶部 3 个 Tab：账单明细 | 收费单明细 | 患者缴费明细
3. 每个 Tab 内一个 DataTable，支持搜索和日期范围筛选
4. Excel 导出
5. 数据源：`invoices` + `invoice_payments` + `invoice_items`
6. 患者手机号脱敏显示（AG-045）
7. Tab 内容懒加载（AG-047）

**G. Radio Button 图表切换**

在以下现有图表型报表中引入 Radio Button 切换模式，同一图表区域通过 Radio 按钮切换数据源，无需刷新页面：

| 报表 | Radio 选项 |
|------|-----------|
| 患者报表-来源分析 Tab | 来源分布 / 年龄段分布 |
| 收费报表（新合并页） | 医生实收 / 收费大类 / 支付方式 |
| 预约分析 | 就诊类型 / 预约医生 / 患者来源 |

- 前端防抖：Radio 切换时取消前一个 AJAX 请求，防止快速切换导致请求堆积

### 侧边栏导航结构（改造后）

```
报表
├── 经营驾驶舱           /business-cockpit
├── 月度经营汇总          /monthly-business-summary-report
├── 医生报表             /doctor-report [Tab: 收费统计 | 工作量统计]
├── 收费报表             /billing-report [Tab: 收费明细 | 项目收入]
├── 患者报表             /patient-report [Tab: 来源分析 | 人口统计]
├── 预约分析             /appointment-analytics-report
├── 复诊率分析            /revisit-rate-report
├── 报价转化             /quotation-conversion-report
├── 治疗计划完成率        /treatment-plan-completion-report
├── 欠费报表             /debtors-report [Tab: 欠费患者 | 欠费账单 | 欠费变动]
├── 财务日历             /financial-calendar [新增]
├── 财务明细             /financial-detail-report [新增, Tab: 账单 | 收费单 | 患者缴费]
├── 现金汇总             /cash-summary-report [新增, Tab: 支付方式 | 日期 | 医生 | 收费大类]
└── 技工单统计            /lab-case-statistics-report [新增]
```

### 异常流程

| 场景 | 处理方式 |
|------|---------|
| 日期范围超过 12 个月 | 后端拒绝查询，前端提示「日期范围不能超过 12 个月」（AG-044） |
| 财务日历某天收入/支出均为 0 | 格子不显示金额，保持空白 |
| 现金汇总中支付方式为 NULL | 归入「其他」类别 |
| Tab 页无数据 | 显示「暂无数据」文案 |
| 访问旧报表 URL | 301 重定向到新合并页面对应 Tab（AG-046） |
| 离职医生数据 | 医生筛选下拉中标注「（已离职）」，历史数据仍可查（AG-029） |
| Excel 导出包含患者信息 | 手机号脱敏，NIN 不出现（AG-045） |

### 涉及的实体变更

| 实体 | 变更类型 | 说明 |
|------|---------|------|
| 无新增表 | — | 全部基于现有表的聚合查询 |
| daily_reports | 读取 | 财务日历可利用已有聚合数据加速查询 |

### 权限矩阵

| 操作 | Super Admin | Admin | Doctor | Nurse | Receptionist |
|------|:-----------:|:-----:|:------:|:-----:|:------------:|
| 查看所有报表 | ✅ | ✅ | ❌ | ❌ | ❌ |
| 财务日历/现金汇总/财务明细 | ✅ | ✅ | ❌ | ❌ | ❌ |
| 技工单统计 | ✅ | ✅ | ❌ | ❌ | ❌ |
| 导出 Excel | ✅ | ✅ | ❌ | ❌ | ❌ |

继续使用现有 `view-reports` 权限，不新增 Slug。

### Rule Key 变更

| Rule Key | 默认值 | 说明 |
|----------|--------|------|
| `report.max_date_range_months` | 12 | 报表日期范围查询最大跨度（月）（AG-044） |

### 涉及修改的文件

| 类别 | 文件 | 修改内容 |
|------|------|---------|
| **Controller（新）** | `FinancialCalendarController.php` | 财务日历 |
| | `CashSummaryReportController.php` | 现金汇总 |
| | `LabCaseStatisticsReportController.php` | 技工单统计 |
| | `FinancialDetailReportController.php` | 财务明细 |
| **Controller（改）** | `DoctorPerformanceReport.php` | 合并为 Tab 页，承载「医生报表」 |
| | `DoctorWorkloadReportController.php` | 合并入医生报表（或数据合并到 DoctorPerformanceReport） |
| | `InvoicingReportsController.php` | 合并为 Tab 页，承载「收费报表」 |
| | `ProceduresReportController.php` | 合并入收费报表 |
| | `PatientSourceReportController.php` | 合并为 Tab 页，承载「患者报表」 |
| | `PatientDemographicsReportController.php` | 合并入患者报表 |
| | `DebtorsReportController.php` | 扩展 Tab（欠费账单/欠费变动） |
| **Service（新）** | `FinancialCalendarService.php` | 日历数据聚合（按天 SUM） |
| | `CashSummaryReportService.php` | 现金汇总查询（4 维度） |
| | `LabCaseStatisticsReportService.php` | 技工单统计聚合 |
| | `FinancialDetailReportService.php` | 财务明细查询 |
| **View（新）** | `reports/financial_calendar.blade.php` | 财务日历页 |
| | `reports/cash_summary_report.blade.php` | 现金汇总页 |
| | `reports/lab_case_statistics_report.blade.php` | 技工单统计页 |
| | `reports/financial_detail_report.blade.php` | 财务明细页 |
| **View（改）** | `reports/doctor_performance_report.blade.php` | 重构为 Tab 页（收费+工作量） |
| | `reports/invoice_payments_report.blade.php` | 重构为 Tab 页（收费明细+项目收入） |
| | `reports/patient_source_report.blade.php` | 重构为 Tab 页（来源+人口统计） |
| | `reports/debtors_report.blade.php` | 扩展 Tab（欠费患者+账单+变动） |
| | 9 个图表型报表 Blade | 拆分内联 CSS 到独立文件 |
| **CSS（新）** | `public/css/business-cockpit.css` 等 9 个 | 从 Blade 拆出的样式 |
| | `public/css/financial-calendar.css` | 财务日历样式 |
| | `public/css/cash-summary.css` | 现金汇总样式 |
| | `public/css/lab-case-statistics.css` | 技工单统计样式 |
| | `public/css/financial-detail.css` | 财务明细样式 |
| **JS（新/改）** | 图表型报表 JS 文件 | Radio Button 切换逻辑 |
| | `public/include_js/financial_calendar.js` | 财务日历交互 |
| | `public/include_js/cash_summary_report.js` | 现金汇总交互 |
| | `public/include_js/lab_case_statistics_report.js` | 技工单统计交互 |
| | `public/include_js/financial_detail_report.js` | 财务明细交互 |
| **Routes** | `routes/web.php` | 新路由 + 旧路由 301 重定向 |
| **Lang** | `resources/lang/{en,zh-CN}/report.php` | 新增翻译 key |
| **Export（新）** | `app/Exports/CashSummaryExport.php` | 现金汇总导出 |
| | `app/Exports/LabCaseStatisticsExport.php` | 技工单统计导出 |
| | `app/Exports/FinancialDetailExport.php` | 财务明细导出 |

### 实施路径

```
第一批（高价值 + 低成本）:
  Phase 1: 内联 CSS 拆分（9 个报表视图）
  Phase 2: Tab 合并 4 组报表（前端重组 + 旧路由重定向）
  Phase 3: 新增财务日历（FullCalendar + invoice_payments/expense_payments）

第二批（中等工作量）:
  Phase 4: 新增现金汇总报表（4 维度 Tab）
  Phase 5: 新增技工单统计报表
  Phase 6: 新增财务明细报表（3 Tab）
  Phase 7: Radio Button 图表切换（应用到 3 个图表型报表）
```

---

## 库房管理增强

### 业务意图
将库存从"记录工具"升级为"业务联动系统"——收费自动扣库存（前台代销）、审批流程（申领/报损/退货）、盘点调整、供应商信息完善。参考口腔云（kqyun.com）库房模块的布局优势，增强现有入库/出库/批次/预警体系。

### 目标用户

| 角色 | 场景 | 权限 slug |
|------|------|-----------|
| 管理员/库管 | 全部操作（含审批、导入、供应商管理） | `manage-inventory` |
| 前台/护士 | 创建出库单、查看库存、盘点、发起报损 | `operate-inventory` |
| 医生 | 提交申领单、查看自己的申领记录 | `request-inventory` |

### 正常流程

#### 前台代销（收费自动扣库存）— 最高优先级
1. 管理员在 ServiceConsumable 配置 `medical_service ↔ inventory_item` 关系（含数量 `qty`、是否必需 `is_required`）
2. 前台创建发票 → `InvoiceService::createInvoice()` 自动查找 `invoice_items` 对应的 ServiceConsumable
3. 按 FIFO 扣减批次库存（悲观锁 `SELECT FOR UPDATE`），生成 `out_type=treatment` 的已确认出库单（AG-048）
4. 同一 `invoice_id` 幂等检查，防止重复扣减（AG-049）
5. 库存不足时弹出警告但允许收费，出库单标注 `stock_insufficient=true`（AG-051）
6. 发票删除 → 事务性回滚出库单 + 恢复批次数量 + 更新 `current_stock`（AG-050）
7. 自动生成的出库单关联 `patient_id` + `appointment_id`

#### 医生申领
1. 医生选择物品和数量 → 创建 `out_type=requisition` 的 StockOut（`status=draft`）
2. 填写完成后提交 → `status=pending_approval`，不可再编辑（AG-052）
3. 库管/管理员审批：
   - 通过 → `status=confirmed`，FIFO 扣减库存，审批人 ≠ 申领人（AG-053）
   - 驳回 → `status=rejected`
4. 单次申领数量上限由 rule-engine 配置（AG-054）
5. 不关联患者——申领是科室领用，非诊疗消耗

#### 报损退货
1. 前台/库管创建报损单（`out_type=damage`）或退货单（`out_type=supplier_return`）
2. 退货单必须填写 `supplier_id`（AG-057）
3. 报损/退货数量不得超过当前库存（AG-056）
4. 提交 → `pending_approval` → 审批通过 → `confirmed`，FIFO 扣减库存
5. 审批人 ≠ 操作人（AG-055）

#### 盘点
1. 选择物品分类 → 系统列出该分类所有物品及 `system_qty` 快照（AG-059）
2. 同一分类同一天限一张未完成盘点单（AG-058）
3. 用户填写 `actual_qty` → 系统计算 `diff_qty = actual_qty - system_qty`
4. 确认盘点：
   - 盘亏（`diff_qty < 0`）→ 自动生成 `out_type=inventory_loss` 的已确认出库单
   - 盘盈（`diff_qty > 0`）→ 自动生成已确认入库单（`supplier_id=null`，notes 标注"盘盈调整"）
5. 偏差超过 50% 需额外确认（AG-060）

#### 今日库房 Dashboard
1. 6 张统计卡片：今日入库单 / 今日出库单 / 待审核申领 / 待审核报损 / 库存预警 / 有效期预警
2. 下方两个列表：库存预警物品（复用 `getLowStockItems()`）+ 有效期预警批次（复用 `getExpiryWarningBatches()`）

#### 库存查询 Tab 化
1. 4 个 Tab：库存汇总 | 批次明细 | 出入库查询 | 出入库明细
2. 全部基于现有数据，只缺前端展示

#### 供应商字段扩展
新增字段：`contact_person`、`phone`、`email`、`address`（text）、`notes`（text）
有关联入库单的供应商不可删除（AG-061）

#### 物品 Excel 导入
1. 下载模板 → 填写物品信息 → 上传 Excel
2. 验证 `item_code` 唯一性，重复行跳过并记录到错误报告（AG-062）
3. 复用 `maatwebsite/excel` 的 `WithValidation` trait

### 异常流程

| 场景 | 处理方式 |
|------|---------|
| 并发扣减同一批次 | 悲观锁 `SELECT FOR UPDATE`，后到者等锁释放后重新检查（AG-048） |
| 发票重复创建 | 幂等检查 `invoice_id` 已存在出库单则跳过（AG-049） |
| 库存不足收费 | 警告但允许，出库单标注 `stock_insufficient`（AG-051） |
| 发票删除回滚 | 同一事务内回滚出库单+批次+current_stock（AG-050） |
| 申领单 pending 后编辑 | 拒绝编辑，提示"已提交审批，不可修改"（AG-052） |
| 自审自批 | 拒绝，提示"审批人不能与申领人相同"（AG-053） |
| 报损数量超库存 | 拒绝，提示"报损数量不能超过当前库存"（AG-056） |
| 退货无供应商 | 验证失败，提示"退货单必须选择供应商"（AG-057） |
| 重复盘点 | 拒绝，提示"该分类今日已有未完成的盘点单"（AG-058） |
| 盘点偏差过大 | 弹出二次确认对话框（AG-060） |
| 已确认单据编辑 | 拒绝编辑/删除（AG-064） |
| current_stock 扣至负数 | 应用层 + 数据库双重阻止（AG-063） |
| 删除在用供应商 | 拒绝，提示"该供应商有关联入库单"（AG-061） |
| Excel 导入重复编码 | 跳过并记录到错误报告（AG-062） |

### 涉及的实体变更

| 实体 | 变更类型 | 说明 |
|------|---------|------|
| Supplier | 扩展字段 | +`contact_person`, `phone`, `email`, `address`, `notes` |
| StockOut | 扩展 enum | `out_type` 新增 `requisition`, `supplier_return`, `inventory_loss` |
| StockOut | 新增字段 | +`recipient`(领用人, string, nullable), +`supplier_id`(退货供应商, FK, nullable), +`approved_by`(审批人, FK users, nullable), +`approved_at`(审批时间, timestamp, nullable), +`stock_insufficient`(库存不足标记, boolean, default false) |
| InventoryCheck | **新增实体** | `check_no`(唯一), `category_id`(FK), `check_date`, `status`(draft/confirmed), `checked_by`(FK users), `confirmed_at`, `notes`, `_who_added`, timestamps, soft_deletes |
| InventoryCheckItem | **新增实体** | `inventory_check_id`(FK, cascade), `inventory_item_id`(FK), `system_qty`, `actual_qty`, `diff_qty`(计算值), `_who_added`, timestamps, soft_deletes |
| Permission | 新增权限 | `operate-inventory`, `request-inventory` |
| DictItem | 新增枚举 | `stock_out_type` 追加 `requisition`, `supplier_return`, `inventory_loss`；`stock_out_status` 追加 `pending_approval`, `rejected` |

### 权限矩阵

| 操作 | 管理员/库管 | 前台/护士 | 医生 |
|------|:-----------:|:---------:|:----:|
| 今日库房 Dashboard | ✅ | ✅ | ❌ |
| 库存查询 | ✅ | ✅ | 只看自己申领的 |
| 创建入库单 | ✅ | ❌ | ❌ |
| 创建出库单 | ✅ | ✅ | ❌ |
| 提交申领单 | ✅ | ❌ | ✅ |
| 审批申领/报损/退货 | ✅ | ❌ | ❌ |
| 创建盘点单 | ✅ | ✅ | ❌ |
| 创建报损/退货单 | ✅ | ✅ | ❌ |
| 供应商管理 | ✅ | ❌ | ❌ |
| 物品 Excel 导入 | ✅ | ❌ | ❌ |
| 前台代销（自动） | 系统自动 | 系统自动 | — |

### Rule Key 变更

| Rule Key | 默认值 | 说明 |
|----------|--------|------|
| `inventory.max_requisition_qty` | 100 | 单次申领数量上限（AG-054） |
| `inventory.check_deviation_threshold` | 0.5 | 盘点偏差审批阈值，超过 system_qty 的 50%（AG-060） |
| `inventory.expiry_warning_days` | 30 | 有效期预警天数 |
| `inventory.price_deviation_threshold` | 0.2 | 入库价格偏差警告阈值（已有） |

### 实施路径

```
Week 1（核心联动 + 低成本）:
  Phase 1: 前台代销（ServiceConsumable → InvoiceService → StockOutService 联动）
  Phase 2: 供应商字段扩展（一个 migration + 表单/列表修改）

Week 2（Dashboard + 查询增强）:
  Phase 3: 今日库房 Dashboard（卡片式总览 + 预警列表）
  Phase 4: 库存查询 Tab 化（4 Tab: 汇总/批次/出入库查询/出入库明细）

Week 3（审批流程）:
  Phase 5: 医生申领单 + 审批（扩展 StockOut 状态机）
  Phase 6: 报损退货审批（复用审批流程）

Week 4（盘点 + 导入）:
  Phase 7: 盘点单（新实体 InventoryCheck + 自动生成调整单）
  Phase 8: 物品 Excel 导入
```

### 库存查询 Tab 化（Phase 4 细节）

独立「库存查询」只读页面（侧边栏新菜单），现有 stock-ins/stock-outs 操作列表页保留。

| Tab | 数据源 | 说明 |
|-----|-------|------|
| 库存汇总 | `inventory_items` + `current_stock` + `stock_warning_level` | 按分类树形/筛选展示；`average_cost` 成本列仅 `manage-inventory` 可见 |
| 批次明细 | `inventory_batches` | 各物品现存批次，含有效期/数量；支持筛选分类/有效期状态 |
| 出入库查询 | `stock_ins` + `stock_outs`（按物品聚合） | 某物品在日期范围内总入库量、总出库量、净变化 |
| 出入库明细 | `stock_ins` + `stock_outs`（每笔流水） | 时间线明细；支持筛选 `out_type`、状态、日期范围 |

### 申领单 + 报损退货审批（Phase 5/6 UI 路径）

- **审批入口**：出库单列表页顶部增加「待审批」快速筛选按钮（含角标数量）；侧边栏「库存管理」菜单右侧显示 Badge
- **驳回后重提**：`rejected` 状态的申领单不可编辑，医生可点击「重新申请」复制为新 `draft`（原 rejected 单保留历史记录）
- **审批统一列表**：`out_type` 列区分申领（requisition）/ 报损（damage）/ 退货（supplier_return），同一审批入口处理

### 盘点单（Phase 7 细节）

- `system_qty` 快照覆盖**该分类所有 active 物品**，创建时批量写入 `inventory_check_items`
- 确认调整单直接 `confirmed`，不走二次审批（偏差超阈值由后端在 `confirm()` 时 enforce AG-067）
- 历史盘点单支持查看已 confirmed 明细（`system_qty` vs `actual_qty` 对比记录）

### Excel 导入模板字段（Phase 8）

| 列名（中文表头） | 对应字段 | 必填 |
|--------------|---------|------|
| 物品编码 | `item_code` | ✅（重复则跳过） |
| 物品名称 | `item_name` | ✅ |
| 分类代码 | `category_code`（联查 FK） | ✅ |
| 单位 | `unit` | ✅ |
| 规格型号 | `specification` | |
| 品牌/厂家 | `brand` | |
| 参考进价 | `reference_price` | |
| 销售价格 | `selling_price` | |
| 有效期管理 | `track_expiry`（是/否） | |
| 安全库存 | `stock_warning_level` | |
| 存放位置 | `storage_location` | |

- 只支持新建，不支持更新（`item_code` 已存在则跳过并记录到错误列表）
- 错误报告以页面内列表形式返回（行号 + 原因），不返回 Excel 文件
- 文件限制 10MB / 5000 行（AG-068）
