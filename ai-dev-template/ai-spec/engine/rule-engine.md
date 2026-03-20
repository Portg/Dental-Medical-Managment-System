# rule-engine.md — 业务规则引擎（口腔医疗管理系统）

## 使用规则
1. 所有业务阈值必须通过 Rule Key 引用，禁止硬编码（AG-020）
2. 修改规则值时只改此文件 + 对应代码中的常量/配置
3. 每个 Rule Key 必须有对应的单元测试

---

## 收费规则

### RULE-FIN-001: 折扣审批阈值
- **Key**: `DISCOUNT_APPROVAL_THRESHOLD`
- **值**: 500（元）
- **规则**: 折扣金额 > 500 元时，自动将 `discount_approval_status` 设为 `pending`
- **关联 Anti-Goal**: AG-001 (BR-035)
- **实现位置**: `Invoice::needsDiscountApproval()`

### RULE-FIN-002: 退款审批阈值
- **Key**: `REFUND_APPROVAL_THRESHOLD`
- **值**: 100（元）
- **规则**: 退款金额 > 100 元时，`approval_status` 设为 `pending`
- **关联 Anti-Goal**: AG-003
- **实现位置**: `Refund::needsApproval()`

### RULE-FIN-003: 折扣优先级
- **Key**: `DISCOUNT_PRIORITY_ORDER`
- **值**: `[member, item, order, coupon]`
- **规则**: 折扣按此优先级顺序计算，依次叠加
- **关联 Anti-Goal**: AG-006
- **实现位置**: `Invoice::calculateDiscounts()`

### RULE-FIN-004: 会员余额下限
- **Key**: `MEMBER_BALANCE_MIN`
- **值**: 0（元）
- **规则**: 会员余额不允许低于此值
- **关联 Anti-Goal**: AG-004

---

## 预约规则

### RULE-APT-001: 椅位冲突检测
- **Key**: `CHAIR_TIME_CONFLICT_CHECK`
- **规则**: 同一 `chair_id` + 同一 `start_date` 时间段不允许重叠
- **关联 Anti-Goal**: AG-010
- **实现位置**: `AppointmentService`

### RULE-APT-002: 爽约次数记录
- **Key**: `NO_SHOW_INCREMENT`
- **规则**: 标记为 `no_show` 时，`no_show_count++`
- **实现位置**: `Appointment::markAsNoShow()`

### RULE-APT-003: 排班窗口校验
- **Key**: `SCHEDULE_WINDOW_VALIDATION`
- **规则**: 创建预约时，后端必须验证预约时间在医生当日某 `work_status='on_duty'` 班次的时间窗口内；`work_status='rest'` 的班次不产生可用时段；时间窗外或无 on_duty 排班（严格模式下）均拒绝创建
- **关联 Anti-Goal**: AG-038, AG-041
- **关联配置**: `clinic.require_schedule_for_booking`（诊所设置，枚举：`on_duty` / `rest`）
- **实现位置**: `AppointmentService::validateScheduleForBooking()`

### RULE-APT-004: max_patients 原子检查
- **Key**: `MAX_PATIENTS_ATOMIC_CHECK`
- **规则**: 同一医生同一班次时间段内的有效预约数（排除 cancelled/no_show）达到 `shift.max_patients` 时，禁止创建新预约；检查必须在数据库事务内使用悲观锁（`lockForUpdate`）完成
- **关联 Anti-Goal**: AG-037, AG-041
- **实现位置**: `AppointmentService::createAppointment()` 内的 DB::transaction 块

---

## 病历规则

### RULE-MED-001: 病历锁定后修改
- **Key**: `MEDICAL_CASE_LOCK_AMENDMENT`
- **规则**: `locked_at` 不为空时，所有修改必须通过 `MedicalCaseAmendment` 审批
- **关联 Anti-Goal**: AG-007, AG-008, AG-009
- **实现位置**: `MedicalCase::canModifyWithoutApproval()`

### RULE-MED-002: 版本号自增
- **Key**: `VERSION_AUTO_INCREMENT`
- **规则**: `version_number` 只在 Amendment 审批通过时自增，不允许手动修改
- **关联 Anti-Goal**: AG-009
- **实现位置**: `MedicalCaseAmendment::approve()`

---

## 技工单规则

### RULE-LAB-001: 返工必须记录原因
- **Key**: `REWORK_REASON_REQUIRED`
- **规则**: 状态变为 `rework` 时，`rework_reason` 不可为空
- **关联 Anti-Goal**: AG-014

### RULE-LAB-002: 预期返回日期计算
- **Key**: `EXPECTED_RETURN_CALCULATION`
- **规则**: `expected_return_date = sent_date + processing_days`
- **实现位置**: `LabCase` model

### RULE-LAB-003: 逾期检测
- **Key**: `OVERDUE_DETECTION`
- **规则**: `expected_return_date < today && status not in [returned, try_in, completed]`
- **实现位置**: `LabCase::getIsOverdue()`

---

## 候诊队列规则

### RULE-QUE-001: 排队号生成
- **Key**: `QUEUE_NUMBER_GENERATION`
- **规则**: 每日每分院从 1 开始递增
- **实现位置**: `WaitingQueue::generateQueueNumber()`

### RULE-QUE-002: 状态同步
- **Key**: `QUEUE_APPOINTMENT_SYNC`
- **规则**: 候诊队列状态变更自动同步到关联的 Appointment
- **实现位置**: `WaitingQueue::startTreatment()`, `WaitingQueue::completeTreatment()`

---

## 数据安全规则

### RULE-SEC-001: NIN 加密
- **Key**: `NIN_ENCRYPTION`
- **规则**: 患者身份证号使用 `EncryptsNin` trait 加密存储
- **关联 Anti-Goal**: AG-015

### RULE-SEC-002: 候诊大屏脱敏
- **Key**: `QUEUE_DISPLAY_MASKING`
- **规则**: 候诊大屏显示患者姓名时使用 `masked_patient_name` 访问器
- **关联 Anti-Goal**: AG-016

### RULE-SEC-003: 软删除
- **Key**: `SOFT_DELETE_ENFORCEMENT`
- **规则**: 所有使用 `SoftDeletes` trait 的 Model 查询必须包含软删除处理
- **关联 Anti-Goal**: AG-019

---

## 库存规则

### RULE-INV-001: 库存扣减原子性
- **Key**: `STOCK_DEDUCTION_ATOMIC`
- **规则**: 库存扣减（FIFO 批次）必须在数据库事务内使用悲观锁（`lockForUpdate`）完成，防止并发超扣
- **关联 Anti-Goal**: AG-048, AG-063
- **实现位置**: `StockOutService::confirmStockOut()`

### RULE-INV-002: 前台代销幂等
- **Key**: `BILLING_STOCK_DEDUCTION_IDEMPOTENT`
- **规则**: 同一 `invoice_id` 不得重复生成出库单；发票创建时先查 `stock_outs.invoice_id` 是否已存在
- **关联 Anti-Goal**: AG-049
- **实现位置**: `InvoiceService::createInvoice()` → `StockOutService`

### RULE-INV-003: 发票删除库存回滚
- **Key**: `INVOICE_DELETE_STOCK_ROLLBACK`
- **规则**: 发票删除时，在同一事务内：删除关联出库单 → 恢复批次数量 → 更新 `current_stock`
- **关联 Anti-Goal**: AG-050
- **实现位置**: `InvoiceService::deleteInvoice()`

### RULE-INV-004: 申领数量上限
- **Key**: `MAX_REQUISITION_QTY`
- **值**: 100
- **规则**: 单次申领单中单个物品的数量不得超过此值
- **关联 Anti-Goal**: AG-054
- **实现位置**: `StockOutItemService::createItem()`

### RULE-INV-005: 盘点偏差阈值
- **Key**: `CHECK_DEVIATION_THRESHOLD`
- **值**: 0.5（50%）
- **规则**: 盘点中任一物品的 `|diff_qty| / system_qty > 0.5` 时，确认盘点需二次审批
- **关联 Anti-Goal**: AG-060
- **实现位置**: `InventoryCheckService::confirmCheck()`

### RULE-INV-006: 有效期预警天数
- **Key**: `EXPIRY_WARNING_DAYS`
- **值**: 30（天）
- **规则**: 批次 `expiry_date` 距今 ≤ 此天数时触发预警
- **实现位置**: `InventoryBatch::isNearExpiry()`

### RULE-INV-007: 入库价格偏差阈值
- **Key**: `PRICE_DEVIATION_THRESHOLD`
- **值**: 0.2（20%）
- **规则**: 入库单价与参考价偏差超过此比例时返回警告，需用户确认
- **实现位置**: `StockInItemService::checkPriceDeviation()`

### RULE-INV-008: 库存金额精度
- **Key**: `INVENTORY_AMOUNT_BCMATH`
- **规则**: `average_cost`、`amount`、加权平均成本计算必须使用 `bcmath`（`bcadd`、`bcmul`、`bcdiv`），scale=2
- **关联 Anti-Goal**: AG-065, AG-005
- **实现位置**: `StockInService::confirmStockIn()`, `StockOutItem` boot event
