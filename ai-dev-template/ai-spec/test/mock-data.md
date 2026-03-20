# mock-data.md — 测试 Mock 数据规范（口腔医疗管理系统）

## 基础测试用户

```php
// 管理员
$admin = User::factory()->create([
    'role_id' => Role::where('slug', 'super-administrator')->first()->id,
    'is_doctor' => 'no',
]);

// 医生
$doctor = User::factory()->create([
    'role_id' => Role::where('slug', 'doctor')->first()->id,
    'is_doctor' => 'yes',
]);

// 前台
$receptionist = User::factory()->create([
    'role_id' => Role::where('slug', 'receptionist')->first()->id,
    'is_doctor' => 'no',
]);

// 护士
$nurse = User::factory()->create([
    'role_id' => Role::where('slug', 'nurse')->first()->id,
    'is_doctor' => 'no',
]);
```

---

## 患者测试数据

```php
// 普通患者
$patient = Patient::factory()->create([
    'status' => 'active',
    'member_balance' => '1000.00',
    'member_points' => 500,
]);

// 有过敏史的患者
$allergicPatient = Patient::factory()->create([
    'drug_allergies' => json_encode(['penicillin', 'latex']),
    'systemic_diseases' => json_encode(['hypertension']),
]);

// 已合并的患者（AG-017 测试用）
$mergedPatient = Patient::factory()->create([
    'status' => 'merged',
    'merged_to_id' => $patient->id,
]);
```

---

## 预约测试数据

```php
// 已排期预约
$appointment = Appointment::factory()->create([
    'status' => Appointment::STATUS_SCHEDULED,
    'patient_id' => $patient->id,
    'doctor_id' => $doctor->id,
    'start_date' => today(),
    'start_time' => '09:00',
    'duration_minutes' => 30,
]);

// 已取消预约（AG-012 测试用）
$cancelledAppointment = Appointment::factory()->create([
    'status' => Appointment::STATUS_CANCELLED,
    'cancelled_reason' => '患者临时有事',
]);
```

---

## 账单测试数据

```php
// 未付款账单
$invoice = Invoice::factory()->create([
    'subtotal' => '1000.00',
    'discount_amount' => '0.00',
    'total_amount' => '1000.00',
    'paid_amount' => '0.00',
    'outstanding_amount' => '1000.00',
    'payment_status' => Invoice::PAYMENT_UNPAID,
]);

// 需要折扣审批的账单（AG-001 测试用）
$discountInvoice = Invoice::factory()->create([
    'subtotal' => '2000.00',
    'discount_amount' => '600.00',  // > 500
    'total_amount' => '1400.00',
    'discount_approval_status' => Invoice::DISCOUNT_PENDING,
]);

// 部分付款账单
$partialInvoice = Invoice::factory()->create([
    'total_amount' => '1000.00',
    'paid_amount' => '500.00',
    'outstanding_amount' => '500.00',
    'payment_status' => Invoice::PAYMENT_PARTIAL,
]);
```

---

## 病历测试数据

```php
// 开放病历
$openCase = MedicalCase::factory()->create([
    'status' => MedicalCase::STATUS_OPEN,
    'locked_at' => null,
    'version_number' => 1,
]);

// 已锁定病历（AG-007 测试用）
$lockedCase = MedicalCase::factory()->create([
    'status' => MedicalCase::STATUS_OPEN,
    'locked_at' => now(),
    'signed_at' => now(),
    'version_number' => 1,
]);
```

---

## 技工单测试数据

```php
// 待送出技工单
$labCase = LabCase::factory()->create([
    'status' => LabCase::STATUS_PENDING,
    'lab_fee' => '200.00',
    'patient_charge' => '500.00',
    'processing_days' => 7,
]);

// 试戴中技工单（AG-014 返工测试用）
$tryInLabCase = LabCase::factory()->create([
    'status' => LabCase::STATUS_TRY_IN,
    'rework_count' => 0,
]);
```

---

## 候诊队列测试数据

```php
// 等待中
$waitingQueue = WaitingQueue::factory()->create([
    'status' => WaitingQueue::STATUS_WAITING,
    'check_in_time' => now(),
    'queue_number' => 1,
]);

// 已叫号
$calledQueue = WaitingQueue::factory()->create([
    'status' => WaitingQueue::STATUS_CALLED,
    'check_in_time' => now()->subMinutes(10),
    'called_time' => now(),
]);
```

---

## 金额边界值数据

```php
// 用于折扣审批阈值测试（RULE-FIN-001）
$discountBoundary = [
    'below' => '499.99',   // 不触发审批
    'at'    => '500.00',   // 不触发审批（>，非>=）
    'above' => '500.01',   // 触发审批
];

// 用于退款审批阈值测试（RULE-FIN-002）
$refundBoundary = [
    'below' => '99.99',    // 不触发审批
    'at'    => '100.00',   // 不触发审批（>，非>=）
    'above' => '100.01',   // 触发审批
];
```

---

## 注意事项
1. 所有 Factory 文件放在 `database/factories/` 目录
2. 金额字段始终使用字符串表示（避免浮点精度问题）
3. 测试数据应自包含，不依赖 seed 数据
4. 使用 `RefreshDatabase` trait 确保测试隔离
