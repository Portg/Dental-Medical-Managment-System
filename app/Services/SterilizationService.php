<?php

namespace App\Services;

use App\SterilizationKit;
use App\SterilizationRecord;
use App\SterilizationUsage;
use App\User;
use App\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SterilizationService
{
    /**
     * 创建灭菌记录（含批次号生成 + 有效期计算）
     */
    public function createRecord(array $data): SterilizationRecord
    {
        return DB::transaction(function () use ($data) {
            $batchNo    = $this->generateBatchNo();
            $expiryDays = SterilizationRecord::EXPIRY_DAYS[$data['method']] ?? 90;
            $sterilizedAt = $data['sterilized_at'] instanceof \Carbon\Carbon
                ? $data['sterilized_at']
                : \Carbon\Carbon::parse($data['sterilized_at']);

            return SterilizationRecord::create([
                'kit_id'           => $data['kit_id'],
                'batch_no'         => $batchNo,
                'method'           => $data['method'],
                'temperature'      => $data['temperature'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'operator_id'      => $data['operator_id'] ?? Auth::id(),
                'sterilized_at'    => $sterilizedAt,
                'expires_at'       => $sterilizedAt->copy()->addDays($expiryDays),
                'status'           => SterilizationRecord::STATUS_VALID,
                'notes'            => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * 批次号生成：S{YYYYMMDD}-{NNN}，行锁防并发重复
     */
    private function generateBatchNo(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "S{$date}-";

        // FOR UPDATE 行锁，确保并发时序号唯一
        $last = DB::table('sterilization_records')
            ->where('batch_no', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderBy('batch_no', 'desc')
            ->value('batch_no');

        $seq = $last ? (int) substr($last, -3) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 登记使用：校验状态 → 创建 usage → 更新 record.status
     * @throws \RuntimeException 若记录已使用或已过期
     */
    public function recordUsage(int $recordId, array $data): SterilizationUsage
    {
        return DB::transaction(function () use ($recordId, $data) {
            $record = SterilizationRecord::lockForUpdate()->findOrFail($recordId);

            if ($record->status === SterilizationRecord::STATUS_USED) {
                throw new \RuntimeException('该灭菌批次已被使用，无法重复登记');
            }
            if ($record->isExpired()) {
                throw new \RuntimeException('该灭菌批次已过期，无法登记使用');
            }

            // 查询冗余快照字段
            $kit     = SterilizationKit::find($record->kit_id);
            $doctor  = User::find($data['used_by']);
            $patient = isset($data['patient_id']) ? Patient::find($data['patient_id']) : null;

            $usage = SterilizationUsage::create([
                'record_id'      => $record->id,
                'appointment_id' => $data['appointment_id'] ?? null,
                'patient_id'     => $data['patient_id'] ?? null,
                'used_by'        => $data['used_by'],
                'used_at'        => $data['used_at'],
                'notes'          => $data['notes'] ?? null,
                // 冗余快照
                'patient_name'   => $patient ? $patient->full_name : null,
                'doctor_name'    => $doctor ? $doctor->full_name : null,
                'kit_name'       => $kit ? $kit->name : null,
                'batch_no'       => $record->batch_no,
            ]);

            $record->update(['status' => SterilizationRecord::STATUS_USED]);

            return $usage;
        });
    }

    /**
     * 撤销使用（软删除 usage + 回滚 record.status）
     */
    public function revokeUsage(int $usageId): void
    {
        DB::transaction(function () use ($usageId) {
            $usage = SterilizationUsage::findOrFail($usageId);
            $usage->delete(); // 软删除

            SterilizationRecord::where('id', $usage->record_id)
                ->update(['status' => SterilizationRecord::STATUS_VALID]);
        });
    }

    /**
     * 更新灭菌记录（仅允许修改 valid 状态的记录）
     */
    public function updateRecord(int $id, array $data): bool
    {
        $record = SterilizationRecord::findOrFail($id);
        if ($record->status !== SterilizationRecord::STATUS_VALID) {
            throw new \RuntimeException('已使用或已作废的记录不可修改');
        }

        $expiryDays = SterilizationRecord::EXPIRY_DAYS[$data['method'] ?? $record->method] ?? 90;
        $sterilizedAt = isset($data['sterilized_at'])
            ? \Carbon\Carbon::parse($data['sterilized_at'])
            : $record->sterilized_at;

        return (bool) $record->update([
            'kit_id'           => $data['kit_id'] ?? $record->kit_id,
            'method'           => $data['method'] ?? $record->method,
            'temperature'      => $data['temperature'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'operator_id'      => $data['operator_id'] ?? $record->operator_id,
            'sterilized_at'    => $sterilizedAt,
            'expires_at'       => $sterilizedAt->copy()->addDays($expiryDays),
            'notes'            => $data['notes'] ?? null,
        ]);
    }

    /**
     * 列表查询（含实时过期标记）
     */
    public function getRecordList(array $filters = []): \Illuminate\Support\Collection
    {
        $query = DB::table('sterilization_records')
            ->leftJoin('sterilization_kits', 'sterilization_kits.id', '=', 'sterilization_records.kit_id')
            ->leftJoin('users', 'users.id', '=', 'sterilization_records.operator_id')
            ->whereNull('sterilization_records.deleted_at')
            ->select([
                'sterilization_records.*',
                'sterilization_kits.name as kit_name',
                'sterilization_kits.kit_no',
                DB::raw(
                    app()->getLocale() === 'zh-CN'
                        ? "CONCAT(IFNULL(users.surname, ''), IFNULL(users.othername, '')) as operator_name"
                        : "TRIM(CONCAT(IFNULL(users.surname, ''), ' ', IFNULL(users.othername, ''))) as operator_name"
                ),
                DB::raw("
                    CASE
                        WHEN sterilization_records.status = 'used'   THEN 'used'
                        WHEN sterilization_records.expires_at < NOW() THEN 'expired'
                        WHEN sterilization_records.expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiring'
                        ELSE 'valid'
                    END AS display_status
                "),
            ]);

        if (!empty($filters['kit_id'])) {
            $query->where('sterilization_records.kit_id', $filters['kit_id']);
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $query->where('sterilization_records.status', 'valid')
                      ->where('sterilization_records.expires_at', '<', now());
            } elseif ($filters['status'] === 'valid') {
                $query->where('sterilization_records.status', 'valid')
                      ->where('sterilization_records.expires_at', '>=', now());
            } else {
                $query->where('sterilization_records.status', $filters['status']);
            }
        }
        if (!empty($filters['date_from'])) {
            $query->where('sterilization_records.sterilized_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('sterilization_records.sterilized_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->orderBy('sterilization_records.sterilized_at', 'desc')->get();
    }
}
