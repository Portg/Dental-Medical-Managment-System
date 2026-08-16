<?php

namespace App\Services;

use App\ClinicDisinfectionRecord;
use App\EquipmentMaintenanceRecord;
use App\MedicalWasteHandoverRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClinicAffairsService
{
    public function disinfectionQuery(array $filters = []): Builder
    {
        $query = ClinicDisinfectionRecord::with(['operator', 'reviewer']);
        $this->scopeBranch($query);

        if (!empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }
        if (!empty($filters['check_type'])) {
            $query->where('check_type', $filters['check_type']);
        }

        return $query->orderByDesc('performed_at');
    }

    public function equipmentQuery(array $filters = []): Builder
    {
        $query = EquipmentMaintenanceRecord::with('operator');
        $this->scopeBranch($query);

        if (!empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (!empty($filters['due'])) {
            if ($filters['due'] === 'overdue') {
                $query->whereNotNull('next_due_at')->whereDate('next_due_at', '<', today());
            } elseif ($filters['due'] === 'soon') {
                $query->whereBetween('next_due_at', [today(), today()->copy()->addDays(30)]);
            }
        }

        return $query->orderByDesc('performed_at');
    }

    public function wasteQuery(array $filters = []): Builder
    {
        $query = MedicalWasteHandoverRecord::with('handler');
        $this->scopeBranch($query);

        if (!empty($filters['waste_type'])) {
            $query->where('waste_type', $filters['waste_type']);
        }

        return $query->orderByDesc('handed_over_at');
    }

    public function createDisinfection(array $data): ClinicDisinfectionRecord
    {
        return ClinicDisinfectionRecord::create($this->auditData($data, 'operator_id'));
    }

    public function updateDisinfection(int $id, array $data): ClinicDisinfectionRecord
    {
        $record = $this->findForBranch(ClinicDisinfectionRecord::query(), $id);
        if ($record->reviewed_at) {
            throw new \RuntimeException(__('clinic_affairs.reviewed_record_locked'));
        }
        $record->update($data);
        return $record->fresh(['operator', 'reviewer']);
    }

    /**
     * 复核只能做一次。
     *
     * updateDisinfection() 与 delete() 都拦「已复核」，唯独复核接口本身不看
     * reviewed_at —— 再点一次就把复核人和复核时间换成后来者，原始的合规签名
     * 无迹可寻。院感记录的复核签名正是要留痕的那一项，允许覆盖就没意义了。
     * 要改复核结论应当先撤销复核（目前没有这个动作），而不是静默重写。
     *
     * 判定必须放进带 whereNull('reviewed_at') 的更新里，靠影响行数决定谁抢到。
     * 「先读 reviewed_at 再更新」挡不住并发：两个人同时点复核，都会读到 null，
     * 后落地的一份照样把复核人和时间盖掉 —— 与 SatisfactionSurveyService
     * 里 regenerateToken()/submitSurvey() 用的是同一条路子。
     */
    public function reviewDisinfection(int $id): ClinicDisinfectionRecord
    {
        // 仍先按门店取一次：不属于本门店的记录应当是 404，而不是「已复核」
        $record = $this->findForBranch(ClinicDisinfectionRecord::query(), $id);

        $affected = ClinicDisinfectionRecord::where('id', $record->id)
            ->whereNull('reviewed_at')
            ->update(['reviewer_id' => Auth::id(), 'reviewed_at' => now()]);

        if ($affected === 0) {
            throw new \RuntimeException(__('clinic_affairs.already_reviewed'));
        }

        return $record->fresh(['operator', 'reviewer']);
    }

    public function createEquipment(array $data): EquipmentMaintenanceRecord
    {
        return EquipmentMaintenanceRecord::create($this->auditData($data, 'operator_id'));
    }

    public function updateEquipment(int $id, array $data): EquipmentMaintenanceRecord
    {
        $record = $this->findForBranch(EquipmentMaintenanceRecord::query(), $id);
        $record->update($data);
        return $record->fresh('operator');
    }

    public function createWaste(array $data): MedicalWasteHandoverRecord
    {
        return MedicalWasteHandoverRecord::create($this->auditData($data, 'handler_id'));
    }

    public function updateWaste(int $id, array $data): MedicalWasteHandoverRecord
    {
        $record = $this->findForBranch(MedicalWasteHandoverRecord::query(), $id);
        $record->update($data);
        return $record->fresh('handler');
    }

    public function delete(string $type, int $id): void
    {
        $models = [
            'disinfection' => ClinicDisinfectionRecord::query(),
            'equipment' => EquipmentMaintenanceRecord::query(),
            'waste' => MedicalWasteHandoverRecord::query(),
        ];
        if (!isset($models[$type])) {
            throw new \InvalidArgumentException('Unknown clinic affairs record type.');
        }

        $record = $this->findForBranch($models[$type], $id);
        if ($type === 'disinfection' && $record->reviewed_at) {
            throw new \RuntimeException(__('clinic_affairs.reviewed_record_locked'));
        }
        $record->delete();
    }

    public function getStats(): array
    {
        $disinfection = ClinicDisinfectionRecord::query();
        $equipment = EquipmentMaintenanceRecord::query();
        $waste = MedicalWasteHandoverRecord::query();
        $this->scopeBranch($disinfection);
        $this->scopeBranch($equipment);
        $this->scopeBranch($waste);

        return [
            'today_disinfection' => (clone $disinfection)->whereDate('performed_at', today())->count(),
            'open_issues' => (clone $disinfection)->where('result', 'issue')->whereNull('reviewed_at')->count(),
            'equipment_due' => (clone $equipment)->whereNotNull('next_due_at')
                ->whereDate('next_due_at', '<=', today()->copy()->addDays(30))->count(),
            'monthly_waste_kg' => (float) (clone $waste)->whereBetween('handed_over_at', [
                now()->startOfMonth(), now()->endOfMonth(),
            ])->sum('weight_kg'),
        ];
    }

    private function auditData(array $data, string $operatorField): array
    {
        $data['branch_id'] = optional(Auth::user())->branch_id;
        $data[$operatorField] = Auth::id();
        return $data;
    }

    private function scopeBranch(Builder $query): void
    {
        $branchId = optional(Auth::user())->branch_id;
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
    }

    private function findForBranch(Builder $query, int $id)
    {
        $this->scopeBranch($query);
        return $query->findOrFail($id);
    }
}
