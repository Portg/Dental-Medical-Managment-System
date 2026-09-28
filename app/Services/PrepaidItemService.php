<?php

namespace App\Services;

use App\InvoiceItem;
use App\PrepaidItemUsage;
use Illuminate\Support\Facades\DB;

/**
 * 剩余项目 —— 已收费但还没做完的那部分。
 *
 * 这块补的是一个财务上的洞：预收款（洁牙次卡、正畸全程、种植分期）收进来了，
 * 服务还欠着，而系统里原先只有「计划」（quotations）和「已收钱」（invoice_items）
 * 两头，中间没有任何东西回答「这个患者交的钱里还有多少没兑现」。
 *
 * 模型只有两件事：
 *   - 权属 = 一条 invoice_items（那笔付费本身），不另造「次卡」实体
 *   - 余量 = 该行 qty - 核销流水之和，不存字段
 * 理由分别见 prepaid_item_usages 建表迁移与 medical_services.track_delivery 的注释。
 */
class PrepaidItemService
{
    /**
     * 患者的剩余项目。
     *
     * 只收 track_delivery 的项目 —— 划价发生在治疗之后，普通收费行开出来
     * 那一刻服务就交付完了，全算进来会把列表刷满（见 track_delivery 注释）。
     *
     * 先买的排前面：次卡这类东西诊所的习惯是先买先用完，剩余金额按各自的
     * 实收单价算才对得上账。
     */
    public function getRemainingForPatient(int $patientId): array
    {
        $rows = $this->baseQuery()
            ->where('inv.patient_id', $patientId)
            ->orderBy('inv.invoice_date')
            ->orderBy('ii.id')
            ->get();

        return $rows
            ->map(fn ($row) => $this->presentRow($row))
            ->filter(fn ($row) => $row['remaining_qty'] > 0)
            ->values()
            ->toArray();
    }

    /**
     * 汇总 —— 收费窗口的角标与患者页概览用。
     *
     * prepaid_value 才是负债口径：钱已经收了、服务还欠着。remaining_value
     * 是按折后价算的应交付服务价值，两者在有欠费的行上并不相等。
     */
    public function getPatientSummary(int $patientId): array
    {
        $rows = $this->getRemainingForPatient($patientId);

        return [
            'item_count'      => count($rows),
            'remaining_qty'   => round(array_sum(array_column($rows, 'remaining_qty')), 2),
            'remaining_value' => round(array_sum(array_column($rows, 'remaining_value')), 2),
            'prepaid_value'   => round(array_sum(array_column($rows, 'prepaid_value')), 2),
        ];
    }

    /**
     * 核销一次。
     *
     * 整个过程在事务里，并对那条收费行 lockForUpdate：两个前台同时点「用一次」
     * 时，两边各自读到「还剩 1 次」然后都写入，余量就成了 -1。锁住收费行把
     * 同一份权属上的核销串行化 —— 余量是从这一行算出来的，锁它就够。
     */
    public function consume(int $invoiceItemId, float $qty, array $context, int $userId): array
    {
        if ($qty <= 0) {
            return ['success' => false, 'message' => __('prepaid.qty_must_be_positive')];
        }

        return DB::transaction(function () use ($invoiceItemId, $qty, $context, $userId) {
            $row = $this->baseQuery()
                ->where('ii.id', $invoiceItemId)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                return ['success' => false, 'message' => __('prepaid.item_not_trackable')];
            }

            $remaining = $this->remainingQty($row);

            // 用 round 再比：qty 是 decimal(8,2)，浮点累加后 3 - 3 可能是 -4e-16，
            // 直接比会把「正好用完最后一次」判成超额
            if (round($qty - $remaining, 2) > 0) {
                return [
                    'success' => false,
                    'message' => __('prepaid.exceeds_remaining', ['remaining' => $this->trimQty($remaining)]),
                ];
            }

            $usage = PrepaidItemUsage::create([
                'invoice_item_id' => $invoiceItemId,
                'patient_id'      => $row->patient_id,
                'qty'             => $qty,
                'used_at'         => $context['used_at'] ?? date('Y-m-d'),
                'medical_case_id' => $context['medical_case_id'] ?? null,
                'appointment_id'  => $context['appointment_id'] ?? null,
                // 没指定就记在这笔收费的接诊医生名下 —— 核销是「谁做了这次」，
                // 空着的话后面查「这次是谁做的」永远查不出来
                'doctor_id'       => $context['doctor_id'] ?? $row->doctor_id,
                'notes'           => $context['notes'] ?? null,
                '_who_added'      => $userId,
            ]);

            return [
                'success'       => true,
                'usage_id'      => $usage->id,
                'remaining_qty' => round($remaining - $qty, 2),
            ];
        });
    }

    /**
     * 撤销一次核销 —— 点错了要能退回去，否则前台只能再开一张单找平。
     */
    public function revoke(int $usageId, int $userId): array
    {
        $usage = PrepaidItemUsage::find($usageId);

        if (!$usage) {
            return ['success' => false, 'message' => __('prepaid.usage_not_found')];
        }

        $usage->delete();

        return ['success' => true, 'invoice_item_id' => $usage->invoice_item_id];
    }

    /**
     * 某一笔权属的核销流水 —— 患者说「我明明还剩两次」时要查的就是这个。
     */
    public function getUsageHistory(int $invoiceItemId): array
    {
        return PrepaidItemUsage::where('invoice_item_id', $invoiceItemId)
            ->with('doctor')
            ->orderBy('used_at')
            ->orderBy('id')
            ->get()
            ->map(function (PrepaidItemUsage $usage) {
                return [
                    'id'          => $usage->id,
                    'qty'         => $this->trimQty((float) $usage->qty),
                    'used_at'     => $usage->used_at ? date('Y-m-d', strtotime((string) $usage->used_at)) : '',
                    'doctor_name' => $usage->doctor
                        ? \App\Http\Helper\NameHelper::join($usage->doctor->surname, $usage->doctor->othername)
                        : '',
                    'notes'       => $usage->notes ?? '',
                ];
            })
            ->toArray();
    }

    /**
     * 权属的取数口径。核销与列表共用同一个 query，两边口径不一致的话，
     * 会出现列表里看不见、却核销得掉的行。
     */
    private function baseQuery()
    {
        return DB::table('invoice_items as ii')
            ->join('invoices as inv', 'inv.id', '=', 'ii.invoice_id')
            ->join('medical_services as ms', 'ms.id', '=', 'ii.medical_service_id')
            ->leftJoin(DB::raw('(
                SELECT invoice_item_id, SUM(qty) AS used_qty
                FROM prepaid_item_usages
                WHERE deleted_at IS NULL
                GROUP BY invoice_item_id
            ) as u'), 'u.invoice_item_id', '=', 'ii.id')
            ->where('ms.track_delivery', 1)
            ->whereNull('ii.deleted_at')
            ->whereNull('inv.deleted_at')
            // 退款/核销掉的账单不再欠服务
            ->whereNotIn('inv.payment_status', ['refunded', 'written_off'])
            ->select([
                'ii.id',
                'ii.qty',
                'ii.price',
                'ii.discounted_price',
                'ii.actual_paid',
                'ii.arrears',
                'ii.tooth_no',
                'ii.doctor_id',
                'inv.id as invoice_id',
                'inv.invoice_no',
                'inv.invoice_date',
                'inv.patient_id',
                'inv.payment_status',
                'ms.name as service_name',
                'ms.unit',
                DB::raw('COALESCE(u.used_qty, 0) as used_qty'),
            ]);
    }

    private function remainingQty(object $row): float
    {
        return round((float) $row->qty - (float) $row->used_qty, 2);
    }

    private function presentRow(object $row): array
    {
        $qty       = (float) $row->qty;
        $remaining = $this->remainingQty($row);

        // 单价按「整行金额 ÷ 数量」还原。不用 ms.price：项目价目表随时会改，
        // 而这笔权属值多少钱，取决于当初买的时候按什么价成交
        $unitCharged = $qty > 0 ? (float) $row->discounted_price / $qty : 0.0;
        $unitPaid    = $qty > 0 ? (float) $row->actual_paid / $qty : 0.0;

        return [
            'invoice_item_id' => $row->id,
            'invoice_id'      => $row->invoice_id,
            'invoice_no'      => $row->invoice_no,
            'invoice_date'    => $row->invoice_date ? date('Y-m-d', strtotime((string) $row->invoice_date)) : '',
            'service_name'    => $row->service_name,
            'unit'            => $row->unit ?: '次',
            'tooth_no'        => $row->tooth_no ?? '',
            'total_qty'       => $this->trimQty($qty),
            'used_qty'        => $this->trimQty((float) $row->used_qty),
            'remaining_qty'   => $remaining,
            // 还该交付的服务价值（按折后单价）
            'remaining_value' => round($remaining * $unitCharged, 2),
            // 已收钱却还没兑现的部分 —— 这个才是负债口径
            'prepaid_value'   => round($remaining * $unitPaid, 2),
            // 这一行本身还欠多少：有欠费的权属不是真「预收」，前台得看得见
            'arrears'         => round((float) $row->arrears, 2),
            'payment_status'  => $row->payment_status,
        ];
    }

    /**
     * 数量去掉无意义的小数尾巴：3.00 次显示成 3，1.50 期保留成 1.5。
     */
    private function trimQty(float $qty): float
    {
        return round($qty, 2) + 0;
    }
}
