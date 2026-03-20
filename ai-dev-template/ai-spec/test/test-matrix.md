# test-matrix.md — 测试矩阵（口腔医疗管理系统）

## Anti-Goals 测试覆盖矩阵

| Anti-Goal | 描述                           | 测试类型    | 测试文件                        | 状态 |
|-----------|-------------------------------|------------|--------------------------------|------|
| AG-001    | 折扣 > 500 元须审批            | Unit       | InvoiceTest                    | 待写 |
| AG-002    | 退款不超过已付金额             | Unit       | RefundTest                     | 待写 |
| AG-003    | 退款 > 100 元须审批            | Unit       | RefundTest                     | 待写 |
| AG-004    | 会员余额不为负                 | Unit       | PatientTest                    | 待写 |
| AG-005    | 金额必须用 bcmath              | Unit       | InvoiceServiceTest             | 待写 |
| AG-006    | 折扣优先级顺序                 | Unit       | InvoiceTest                    | 待写 |
| AG-007    | 病历锁定后走 Amendment         | Unit       | MedicalCaseTest                | 待写 |
| AG-008    | Amendment 拒绝不自动应用       | Unit       | MedicalCaseAmendmentTest       | 待写 |
| AG-009    | version_number 不手动修改      | Unit       | MedicalCaseAmendmentTest       | 待写 |
| AG-010    | 同时段同椅位不重复预约          | Feature    | AppointmentControllerTest      | 待写 |
| AG-011    | 候诊不能跳过状态               | Unit       | WaitingQueueTest               | 待写 |
| AG-012    | 取消预约不能重新完成            | Unit       | AppointmentTest                | 待写 |
| AG-013    | 技工单不能逆向流转             | Unit       | LabCaseTest                    | 待写 |
| AG-014    | 返工必须记录原因               | Unit       | LabCaseTest                    | 待写 |
| AG-015    | NIN 不出现在日志               | Feature    | SecurityTest                   | 待写 |
| AG-016    | 候诊大屏脱敏显示               | Unit       | WaitingQueueTest               | 待写 |
| AG-017    | 合并患者不物理删除             | Unit       | PatientTest                    | 待写 |
| AG-018    | 会员密码不明文存储             | Unit       | PatientTest                    | 待写 |
| AG-019    | 软删除查询正确处理             | Feature    | SoftDeleteTest                 | 待写 |
| AG-020    | 阈值不硬编码                   | Review     | Code Review                    | 持续 |

---

## 状态机测试矩阵

### Appointment 状态流转
| 起始状态              | 目标状态              | 合法 | 测试状态 |
|----------------------|----------------------|------|---------|
| waiting              | scheduled            | Yes  | 待写    |
| waiting              | cancelled            | Yes  | 待写    |
| scheduled            | checked_in           | Yes  | 待写    |
| scheduled            | rescheduled          | Yes  | 待写    |
| scheduled            | cancelled            | Yes  | 待写    |
| scheduled            | no_show              | Yes  | 待写    |
| checked_in           | in_progress          | Yes  | 待写    |
| in_progress          | treatment_complete   | Yes  | 待写    |
| treatment_complete   | completed            | Yes  | 待写    |
| waiting              | completed            | No   | 待写    |
| cancelled            | completed            | No   | 待写    |

### WaitingQueue 状态流转
| 起始状态     | 目标状态     | 合法 | 测试状态 |
|-------------|-------------|------|---------|
| waiting     | called      | Yes  | 待写    |
| called      | in_treatment| Yes  | 待写    |
| in_treatment| completed   | Yes  | 待写    |
| waiting     | completed   | No   | 待写    |
| waiting     | in_treatment| No   | 待写    |
| completed   | waiting     | No   | 待写    |

### LabCase 状态流转
| 起始状态       | 目标状态       | 合法 | 测试状态 |
|---------------|---------------|------|---------|
| pending       | sent          | Yes  | 待写    |
| sent          | in_production | Yes  | 待写    |
| in_production | returned      | Yes  | 待写    |
| returned      | try_in        | Yes  | 待写    |
| try_in        | completed     | Yes  | 待写    |
| try_in        | rework        | Yes  | 待写    |
| rework        | sent          | Yes  | 待写    |
| completed     | sent          | No   | 待写    |
| pending       | completed     | No   | 待写    |
| returned      | pending       | No   | 待写    |

---

## 金额边界值测试矩阵

| 测试场景             | 测试值                              | 预期结果                |
|---------------------|-------------------------------------|------------------------|
| 折扣审批阈值        | 499.99, 500.00, 500.01             | 不审批, 不审批, 审批     |
| 退款审批阈值        | 99.99, 100.00, 100.01              | 不审批, 不审批, 审批     |
| 退款金额上限        | paid_amount-0.01, paid_amount, paid_amount+0.01 | 通过, 通过, 拒绝 |
| 会员余额下限        | 0.01, 0.00, -0.01                  | 通过, 通过, 拒绝        |
| 零金额账单          | 0.00                                | 创建成功，状态 paid     |
