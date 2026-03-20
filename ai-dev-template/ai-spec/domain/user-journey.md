# user-journey.md — 用户旅程（口腔医疗管理系统）

## 旅程一：患者首次就诊（完整流程）

### 角色：前台 + 医生 + 护士

```
[前台] 创建患者档案
  ↓ 录入基本信息（姓名、电话、身份证、过敏史、全身疾病）
  ↓ 可选：开通会员（选择等级、收开卡费、初始充值）

[前台] 创建预约
  ↓ 选择医生、椅位、日期时间
  ↓ 系统校验：同一时段同一椅位无冲突（AG-010）
  ↓ 预约状态：scheduled
  ↓ 系统可发送提醒（reminder_sent）

[前台] 患者到店签到
  ↓ 预约状态 → checked_in
  ↓ 自动创建候诊队列（WaitingQueue:waiting）
  ↓ 候诊大屏显示脱敏姓名（AG-016）

[护士] 叫号
  ↓ WaitingQueue:waiting → called
  ↓ 分配椅位
  ↓ 可选：记录生命体征（VitalSign）

[医生] 开始就诊
  ↓ WaitingQueue:called → in_treatment
  ↓ Appointment:in_progress
  ↓ 创建/更新病历（MedicalCase:open）
  ↓  ├─ 主诉（S）
  ↓  ├─ 检查 + 牙位图（O）
  ↓  ├─ 诊断 + ICD10（A）
  ↓  └─ 治疗方案（P）

[医生] 完成治疗
  ↓ WaitingQueue:in_treatment → completed
  ↓ Appointment:treatment_complete → completed
  ↓ 可选：开处方（Prescription）
  ↓ 可选：创建治疗计划（TreatmentPlan）
  ↓ 可选：创建技工单（LabCase:pending）
  ↓ 病历签名 + 锁定（locked_at = now()）

[前台] 收费
  ↓ 创建账单（Invoice）
  ↓ 添加收费项目（InvoiceItem）
  ↓ 计算折扣（四级优先级，AG-006）
  ↓ 折扣 > 500？触发审批（AG-001）
  ↓ 收款（InvoicePayment）
  ↓ 或欠费挂账（is_credit = true）

[前台] 预约下次复诊（可选）
  ↓ 创建新 Appointment
  ↓ 安排随访（PatientFollowup）
```

---

## 旅程二：技工单流程

### 角色：医生 + 前台

```
[医生] 创建技工单
  ↓ LabCase:pending
  ↓ 选择加工所（Lab）
  ↓ 添加项目（LabCaseItem：修复体类型、材料、色号、牙位）
  ↓ 设置加工天数 → 自动计算预计返回日期

[前台] 送出
  ↓ LabCase:pending → sent
  ↓ 记录送出日期

[加工所] 制作中
  ↓ LabCase:sent → in_production

[前台] 收到返回件
  ↓ LabCase:in_production → returned
  ↓ 记录实际返回日期

[医生] 试戴
  ↓ LabCase:returned → try_in
  ↓ 评估是否合格

[医生] 完成 或 返工
  ├─ 合格：LabCase:try_in → completed
  │  ↓ 填写质量评分（1-5）
  └─ 不合格：LabCase:try_in → rework
     ↓ 必须填写返工原因（AG-014）
     ↓ rework_count++
     ↓ LabCase:rework → sent（重新送出）
```

---

## 旅程三：病历修改（合规流程）

### 角色：医生 + 管理员

```
[医生] 发现已锁定病历需要修改
  ↓ 病历 locked_at 不为空 → canModifyWithoutApproval() = false
  ↓ 创建 MedicalCaseAmendment
  ↓  ├─ amendment_reason（修改原因）
  ↓  ├─ amendment_fields（修改的字段列表）
  ↓  ├─ old_values / new_values（修改前后对比）
  ↓  └─ status: pending

[管理员] 审批
  ├─ 批准：Amendment:approved
  │  ↓ 自动应用修改到 MedicalCase
  │  ↓ version_number++ （AG-009：只能通过此途径自增）
  │  ↓ 记录 reviewed_at, review_notes
  └─ 拒绝：Amendment:rejected
     ↓ 记录拒绝原因（AG-008：不自动应用）
```

---

## 旅程四：退款流程

### 角色：前台 + 管理员

```
[前台] 发起退款
  ↓ 创建 Refund
  ↓ 校验：refund_amount <= invoice.paid_amount（AG-002）
  ↓ 退款 > 100 元？→ approval_status: pending（AG-003）
  ↓ 退款 <= 100 元？→ 直接执行

[管理员] 审批（> 100 元）
  ├─ 批准：执行退款，更新 Invoice payment_status
  └─ 拒绝：记录 rejection_reason

[系统] 退款后自动更新
  ↓ Invoice.paid_amount -= refund_amount
  ↓ Invoice.outstanding_amount 重新计算
  ↓ Invoice.payment_status 自动更新
```
