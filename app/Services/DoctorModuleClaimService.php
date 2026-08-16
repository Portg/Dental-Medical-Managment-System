<?php

namespace App\Services;

use App\Appointment;
use App\ClaimRate;
use App\DoctorClaim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorModuleClaimService
{
    /**
     * Get claims list for the current doctor.
     */
    public function getClaimsList(): Collection
    {
        return DB::table('doctor_claims')
            ->join('appointments', 'appointments.id', 'doctor_claims.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('doctor_claims.deleted_at')
            ->where('doctor_claims._who_added', Auth::User()->id)
            ->select('doctor_claims.*', 'patients.surname', 'patients.othername')
            ->orderBy('doctor_claims.updated_at', 'desc')
            ->get();
    }

    /**
     * Calculate insurance claim amount for the current doctor.
     */
    public function calculateInsuranceClaim(float $insuranceAmount): float
    {
        $claimRate = $this->getActiveClaimRate();
        return $claimRate->insurance_rate / 100 * $insuranceAmount;
    }

    /**
     * Calculate cash claim amount for the current doctor.
     */
    public function calculateCashClaim(float $cashAmount): float
    {
        $claimRate = $this->getActiveClaimRate();
        return $claimRate->cash_rate / 100 * $cashAmount;
    }

    /**
     * Calculate total claim amount (insurance + cash) for the current doctor.
     */
    public function calculateTotalClaim(float $insuranceAmount, float $cashAmount): float
    {
        return $this->calculateInsuranceClaim($insuranceAmount) + $this->calculateCashClaim($cashAmount);
    }

    /**
     * Get the active claim rate for the current doctor, or null if none.
     */
    public function getActiveClaimRate(): ?object
    {
        return ClaimRate::where(['doctor_id' => Auth::User()->id, 'status' => 'active'])->first();
    }

    /**
     * Create a new doctor claim.
     *
     * 两条此前只存在于界面上、接口侧完全没有的规则：
     *
     * 1. **只能给自己的预约提成。** 列表和日历都按 appointments.doctor_id 过滤，
     *    但 POST /claims 直接拿请求里的 appointment_id 建记录 —— 换个 id 就能
     *    给同事的接诊开自己的提成单，而且提成率用的是自己的 ClaimRate。
     * 2. **一个预约只提一次。** DoctorAppointmentService::appointmentHasClaim()
     *    只是用来决定要不要显示「申请提成」按钮；接口没跟上，重复 POST 就能
     *    对同一次就诊反复提成。
     *
     * 「查不存在 → 建」之间有并发窗口：连点两次或两个标签页同时提交，两个请求
     * 都会查到没有提成，各建一条。这里靠事务里对预约行 lockForUpdate 把同一个
     * 预约的提成请求排成队 —— 不用 doctor_claims.appointment_id 上的唯一索引，
     * 因为提成是软删的，唯一索引会让「删掉重提」永久失败。
     *
     * @throws \RuntimeException 预约不属于本人，或该预约已经提过成
     * @return DoctorClaim|null  没有生效中的提成比例时返回 null（保持原语义）
     */
    public function createClaim(int $appointmentId, float $amount): ?DoctorClaim
    {
        $doctorId = Auth::User()->id;

        return DB::transaction(function () use ($appointmentId, $amount, $doctorId) {
            // 归属校验与排队一次完成：锁的是预约行，同一预约的后来者要等前一个提交
            $appointment = Appointment::where('id', $appointmentId)
                ->where('doctor_id', $doctorId)
                ->lockForUpdate()
                ->first();

            if ($appointment === null) {
                throw new \RuntimeException(__('doctor_claims.appointment_not_yours'));
            }

            if (DoctorClaim::where('appointment_id', $appointmentId)->exists()) {
                throw new \RuntimeException(__('doctor_claims.claim_already_exists'));
            }

            $claimRate = $this->getActiveClaimRate();
            if ($claimRate === null) {
                return null;
            }

            return DoctorClaim::create([
                'claim_amount' => $amount,
                'appointment_id' => $appointmentId,
                'claim_rate_id' => $claimRate->id,
                '_who_added' => $doctorId,
            ]) ?: null;
        });
    }

    /**
     * Get a claim for editing.
     */
    /**
     * 只认本人、且仍处于待审批的提成。
     *
     * getClaimsList() 一直按 _who_added 过滤，但按 id 取单条的这几个方法此前不过滤，
     * 控制器上也没有任何权限中间件 —— 等于任一登录账号都能改别人的提成金额，
     * 旧实现还顺手把 _who_added 改成自己，等于把记录据为己有。
     *
     * 状态限制与列表的操作菜单一致：只有 Pending 才给编辑/删除按钮，
     * 接口这一侧此前没跟上，已审批的提成照样能被改。
     */
    public function getClaimForEdit(int $id): ?DoctorClaim
    {
        return $this->ownPendingClaim($id)->first();
    }

    /**
     * Update an existing claim.
     */
    public function updateClaim(int $id, float $amount): bool
    {
        return (bool) $this->ownPendingClaim($id)->update([
            'claim_amount' => $amount,
        ]);
    }

    /**
     * Delete a claim (soft-delete).
     */
    public function deleteClaim(int $id): bool
    {
        return (bool) $this->ownPendingClaim($id)->delete();
    }

    /**
     * 当前用户名下、仍待审批的提成记录。
     */
    private function ownPendingClaim(int $id)
    {
        return DoctorClaim::where('id', $id)
            ->where('_who_added', Auth::User()->id)
            ->where('status', DoctorClaim::STATUS_PENDING);
    }
}
