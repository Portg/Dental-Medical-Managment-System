# entities.md — 核心实体定义（口腔医疗管理系统）

## 实体关系总览

```
Patient ─┬─ Appointment ─┬─ WaitingQueue
         │               ├─ MedicalCase ─┬─ Diagnosis
         │               │               ├─ ProgressNote
         │               │               ├─ TreatmentPlan ── TreatmentPlanItem
         │               │               ├─ Prescription
         │               │               ├─ DentalChart
         │               │               ├─ LabCase ── LabCaseItem
         │               │               └─ MedicalCaseAmendment
         │               ├─ Invoice ─┬─ InvoiceItem
         │               │           ├─ InvoicePayment
         │               │           └─ Refund
         │               └─ VitalSign
         ├─ MemberLevel
         └─ PatientFollowup
```

---

## Patient（患者）
| 字段                 | 类型          | 说明                                    |
|---------------------|---------------|----------------------------------------|
| patient_no          | string        | 患者编号（唯一）                         |
| patient_code        | string        | 院区编号（分院 + 日期 + 序号）            |
| status              | enum          | active, merged                          |
| surname/othername   | string        | 姓/名                                   |
| nin                 | encrypted     | 身份证号（加密存储，高敏）                |
| member_no           | string        | 会员号                                   |
| member_level_id     | FK            | 会员等级                                 |
| member_balance      | decimal       | 会员余额（不允许为负）                    |
| member_points       | int           | 积分                                     |
| member_status       | string        | 会员状态                                 |
| merged_to_id        | FK nullable   | 合并目标患者（合并后只标记不删除）         |

**关系**: memberLevel, appointments, invoices, medicalCases, followups

---

## Appointment（预约）
| 字段                | 类型          | 说明                                     |
|--------------------|---------------|------------------------------------------|
| appointment_no     | string        | 预约编号（唯一）                           |
| status             | string(50)    | 见 state-machines.yaml                    |
| appointment_type   | enum          | first_visit, revisit                     |
| visit_information  | string(50)    | walk_in, appointment, single treatment, review treatment |
| start_date/end_date| date          | 预约日期                                  |
| start_time         | time          | 开始时间                                  |
| duration_minutes   | int           | 时长（分钟）                              |
| sort_by            | datetime      | 排序用（日期+时间）                        |
| no_show_count      | int           | 爽约次数                                  |
| reminder_sent      | boolean       | 是否已发送提醒                             |
| confirmed_by_patient| boolean      | 患者是否确认                               |
| shift_id           | FK nullable   | 预约创建时匹配的班次（`nullOnDelete`；班次删除时置 null，不阻止删除；改期/更新预约时不覆盖此字段，保留历史快照） |

**关系**: patient, doctor, chair, branch, service, medicalCase, invoices, waitingQueue, shift

---

## Invoice（账单）
| 字段                      | 类型       | 说明                                  |
|--------------------------|------------|---------------------------------------|
| invoice_no               | string     | 账单编号（唯一）                        |
| subtotal                 | decimal:2  | 小计                                   |
| discount_amount          | decimal:2  | 折扣总额                               |
| total_amount             | decimal:2  | 应付总额                               |
| paid_amount              | decimal:2  | 已付金额                               |
| outstanding_amount       | decimal:2  | 欠款（自动计算：total - paid）          |
| payment_status           | string     | 见 state-machines.yaml                 |
| discount_approval_status | string     | none, pending, approved, rejected      |
| is_credit                | boolean    | 是否欠费挂账                            |
| billing_mode             | string     | 计费模式                               |

**折扣字段（四级优先级）**:
1. member_discount_rate / member_discount_amount
2. item_discount_amount
3. order_discount_rate / order_discount_amount
4. coupon_id / coupon_discount_amount

**关系**: patient, appointment, medicalCase, items, payments, refunds, coupon

---

## MedicalCase（病历）
| 字段                    | 类型       | 说明                                   |
|------------------------|------------|----------------------------------------|
| case_no                | string     | 病历编号（唯一）                         |
| status                 | string     | open, closed, follow-up                |
| chief_complaint        | text       | 主诉（S）                               |
| examination            | text       | 检查（O）                               |
| diagnosis              | text       | 诊断（A）                               |
| treatment              | text       | 治疗（P）                               |
| visit_type             | string     | initial, revisit                       |
| signature              | text       | 电子签名                                |
| locked_at              | timestamp  | 锁定时间（锁定后需 Amendment 才能修改）   |
| version_number         | int        | 版本号（Amendment 审批后自增）            |
| is_draft               | boolean    | 是否草稿                                |

**合规字段**: signed_at, modified_at, modified_by, modification_reason

**关系**: patient, doctor, diagnoses, progressNotes, treatmentPlans, prescriptions, dentalCharts, amendments, labCases

---

## LabCase（技工单）
| 字段                  | 类型       | 说明                                    |
|----------------------|------------|----------------------------------------|
| lab_case_no          | string     | 编号（LC + 日期 + 序号）                 |
| status               | string     | 见 state-machines.yaml                  |
| lab_id               | FK         | 加工所                                   |
| processing_days      | int        | 加工天数                                 |
| expected_return_date | date       | 预计返回日期                              |
| actual_return_date   | date       | 实际返回日期                              |
| lab_fee              | decimal:2  | 加工费                                   |
| patient_charge       | decimal:2  | 患者收费                                 |
| quality_rating       | int(1-5)   | 质量评分                                 |
| rework_count         | int        | 返工次数                                 |
| rework_reason        | text       | 返工原因（返工时必填）                     |

**关系**: patient, doctor, appointment, medicalCase, lab, items(LabCaseItem)

---

## WaitingQueue（候诊队列）
| 字段                   | 类型       | 说明                                   |
|-----------------------|------------|----------------------------------------|
| queue_number          | int        | 当日排队号（分院 + 当日序号）             |
| status                | string     | 见 state-machines.yaml                  |
| visit_type            | string     | 就诊类型                                |
| check_in_time         | timestamp  | 签到时间                                |
| called_time           | timestamp  | 叫号时间                                |
| treatment_start_time  | timestamp  | 治疗开始时间                             |
| treatment_end_time    | timestamp  | 治疗结束时间                             |
| estimated_wait_minutes| int        | 预计等待时间                             |

**访问器**: waited_minutes（计算等待时长）, masked_patient_name（脱敏姓名）

**关系**: patient, appointment, doctor, chair, branch

---

## TreatmentPlan（治疗计划）
| 字段                    | 类型       | 说明                                  |
|------------------------|------------|---------------------------------------|
| plan_name              | string     | 计划名称                               |
| status                 | string     | Planned, In Progress, Completed, Cancelled |
| approval_status        | string     | pending, approved, rejected, revision_needed |
| total_price            | decimal:2  | 总价                                  |
| discount_rate          | decimal    | 折扣率                                |
| final_price            | decimal:2  | 最终价格（自动计算）                    |
| confirmed_by           | FK         | 确认人                                 |
| electronic_signature   | text       | 电子签名                               |

**关系**: medicalCase, patient, stages, items(TreatmentPlanItem)
