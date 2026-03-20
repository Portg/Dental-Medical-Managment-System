# error-codes.md — 错误码定义（口腔医疗管理系统）

## 错误码格式

JSON 响应统一格式：
```json
{
    "message": "错误描述（国际化）",
    "status": 0,
    "data": null
}
```

API v1 响应格式：
```json
{
    "success": false,
    "message": "错误描述（国际化）",
    "data": null
}
```

---

## 通用错误

| 错误码场景              | message 示例                    | HTTP Status |
|------------------------|--------------------------------|-------------|
| 验证失败               | Validator 第一条错误信息         | 200 (status=0) / 422 (API) |
| 记录不存在             | "Record not found"              | 404         |
| 无权限                 | "Unauthorized"                  | 403         |
| 未认证                 | "Unauthenticated"              | 401         |

---

## 预约模块错误

| 场景                          | message                                              |
|-------------------------------|------------------------------------------------------|
| 时段椅位冲突 (AG-010)         | appointment.chair_time_conflict                      |
| 医生该时段已满                 | appointment.doctor_fully_booked                      |
| 预约日期已过                   | appointment.date_in_past                             |
| 取消后试图完成 (AG-012)       | appointment.cannot_complete_cancelled                 |
| 超出排班时间                   | appointment.outside_schedule                          |

---

## 收费模块错误

| 场景                          | message                                              |
|-------------------------------|------------------------------------------------------|
| 折扣需审批 (AG-001)           | invoice.discount_requires_approval                   |
| 折扣审批待处理，不可收款       | invoice.payment_blocked_by_pending_approval           |
| 退款超过已付金额 (AG-002)     | invoice.refund_exceeds_paid_amount                   |
| 退款需审批 (AG-003)           | invoice.refund_requires_approval                     |
| 会员余额不足 (AG-004)         | invoice.insufficient_member_balance                  |
| 优惠券已失效                   | invoice.coupon_expired                               |
| 优惠券使用次数已满             | invoice.coupon_usage_limit_reached                   |
| 未达优惠券最低消费             | invoice.coupon_min_order_not_met                     |

---

## 病历模块错误

| 场景                          | message                                              |
|-------------------------------|------------------------------------------------------|
| 病历已锁定 (AG-007)           | medical_case.locked_requires_amendment               |
| 修改申请被拒 (AG-008)         | medical_case.amendment_rejected                      |
| 版本号冲突 (AG-009)           | medical_case.version_conflict                        |
| 存在待处理修改申请             | medical_case.has_pending_amendment                   |
| 病历已关闭不可修改             | medical_case.closed_not_editable                     |

---

## 技工单模块错误

| 场景                          | message                                              |
|-------------------------------|------------------------------------------------------|
| 非法状态流转 (AG-013)         | lab_case.invalid_status_transition                   |
| 返工缺少原因 (AG-014)         | lab_case.rework_reason_required                      |
| 技工单已完成不可修改            | lab_case.completed_not_editable                      |

---

## 候诊队列错误

| 场景                          | message                                              |
|-------------------------------|------------------------------------------------------|
| 非法状态跳跃 (AG-011)         | waiting_queue.invalid_status_transition              |
| 当前无等候患者                 | waiting_queue.no_waiting_patients                    |
| 患者已在队列中                 | waiting_queue.patient_already_in_queue               |

---

## 患者模块错误

| 场景                          | message                                              |
|-------------------------------|------------------------------------------------------|
| 患者已被合并 (AG-017)         | patient.already_merged                               |
| 手机号已存在                   | patient.phone_already_exists                         |
| 会员号已存在                   | patient.member_no_already_exists                     |
