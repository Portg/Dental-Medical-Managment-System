# CONTEXT-SLICE.md — 任务上下文切片规则（口腔医疗管理系统）

## 概述

不同任务只需加载最小必要上下文，避免 AI 上下文窗口浪费。
每次任务启动时，按任务类型查表加载对应切片。

---

## 切片 A：预约管理任务

加载：
1. `@ai-spec/domain/intent.md`（Anti-Goals 全文）
2. `@ai-spec/domain/state-machines.yaml`（appointment 部分）
3. `@ai-spec/domain/entities.md`（Appointment, WaitingQueue, Chair, DoctorSchedule）
4. `@CLAUDE.md`
5. `@TEST-SPEC.md`

跳过：Invoice 相关、LabCase 相关、Prescription 相关

---

## 切片 B：收费与账单任务

加载：
1. `@ai-spec/domain/intent.md`（Anti-Goals: BR-035 折扣审批、退款审批）
2. `@ai-spec/engine/rule-engine.md`（折扣规则、审批阈值）
3. `@ai-spec/domain/entities.md`（Invoice, InvoiceItem, InvoicePayment, Refund, Coupon）
4. `@ai-spec/domain/state-machines.yaml`（invoice_payment, discount_approval 部分）
5. `@CLAUDE.md`
6. `@TEST-SPEC.md`

跳过：MedicalCase 详情、LabCase 相关、DoctorSchedule

---

## 切片 C：病历管理任务

加载：
1. `@ai-spec/domain/intent.md`（Anti-Goals: 病历合规性）
2. `@ai-spec/domain/entities.md`（MedicalCase, Diagnosis, ProgressNote, TreatmentPlan, Prescription, DentalChart）
3. `@ai-spec/domain/state-machines.yaml`（medical_case, amendment 部分）
4. `@CLAUDE.md`
5. `@TEST-SPEC.md`

跳过：Invoice 计算细节、LabCase 流程、预约调度

---

## 切片 D：技工单管理任务

加载：
1. `@ai-spec/domain/intent.md`（Anti-Goals: 技工单相关）
2. `@ai-spec/domain/entities.md`（LabCase, LabCaseItem, Lab）
3. `@ai-spec/domain/state-machines.yaml`（lab_case 部分）
4. `@CLAUDE.md`
5. `@TEST-SPEC.md`

跳过：Invoice 详情、MedicalCase SOAP 内容、处方

---

## 切片 E：患者管理任务

加载：
1. `@ai-spec/domain/intent.md`
2. `@ai-spec/domain/entities.md`（Patient, MemberLevel, Coupon）
3. `@ai-spec/engine/rule-engine.md`（会员规则、积分规则）
4. `@CLAUDE.md`

跳过：MedicalCase 详情、LabCase 详情、状态机

---

## 切片 F：候诊队列任务

加载：
1. `@ai-spec/domain/intent.md`
2. `@ai-spec/domain/entities.md`（WaitingQueue, Appointment, Chair）
3. `@ai-spec/domain/state-machines.yaml`（waiting_queue, appointment 部分）
4. `@CLAUDE.md`
5. `@TEST-SPEC.md`

跳过：Invoice、MedicalCase 详情、LabCase、处方

---

## 切片 G：测试生成任务

加载：
1. `@ai-spec/domain/intent.md`（Anti-Goals 全文）
2. `@ai-spec/domain/state-machines.yaml`
3. `@ai-spec/engine/rule-engine.md`
4. `@TEST-SPEC.md`
5. [待测试的代码文件]

跳过：user-journey.md

---

## 切片 H：Prompt 版本审查

加载：
1. `@PROMPT-VERSIONS/[Prompt名]/CHANGELOG.md`
2. `@PROMPT-GOLDEN-TESTS.md`（对应测试用例）
3. [上次使用该 Prompt 生成的代码样本]

跳过：业务 spec 文件

---

## 切片 I：国际化任务

加载：
1. 待处理的 Blade / JS / Controller 文件
2. `resources/lang/zh-CN/` 对应模块翻译文件
3. `resources/lang/en/` 对应模块翻译文件
4. `@CLAUDE.md`（国际化规范部分）

跳过：所有 ai-spec 文件、状态机、规则引擎

---

## 切片 J：API 开发任务

加载：
1. `@ai-spec/domain/intent.md`
2. `@ai-spec/domain/entities.md`（涉及的实体）
3. `@ai-spec/contracts/error-codes.md`
4. `@CLAUDE.md`（API 相关部分）
5. `routes/api/v1.php`
6. `@TEST-SPEC.md`

跳过：Blade 模板、前端 CSS/JS、user-journey.md
