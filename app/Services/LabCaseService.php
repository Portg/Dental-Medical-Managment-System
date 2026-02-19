<?php

namespace App\Services;

use App\LabCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class LabCaseService
{
    /**
     * Get lab cases list with filters.
     */
    public function getLabCaseList(array $filters = []): Collection
    {
        $query = DB::table('lab_cases')
            ->join('patients', 'patients.id', 'lab_cases.patient_id')
            ->join('users', 'users.id', 'lab_cases.doctor_id')
            ->join('labs', 'labs.id', 'lab_cases.lab_id')
            ->whereNull('lab_cases.deleted_at')
            ->select(
                'lab_cases.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.patient_no',
                'users.othername as doctor_name',
                'labs.name as lab_name'
            );

        if (!empty($filters['status'])) {
            $query->where('lab_cases.status', $filters['status']);
        }

        if (!empty($filters['lab_id'])) {
            $query->where('lab_cases.lab_id', $filters['lab_id']);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('lab_cases.doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('lab_cases.lab_case_no', 'like', "%{$search}%")
                  ->orWhere('patients.surname', 'like', "%{$search}%")
                  ->orWhere('patients.othername', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('lab_cases.updated_at', 'desc')->get();
    }

    /**
     * Get overdue lab cases.
     */
    public function getOverdueCases(): Collection
    {
        return DB::table('lab_cases')
            ->join('patients', 'patients.id', 'lab_cases.patient_id')
            ->join('labs', 'labs.id', 'lab_cases.lab_id')
            ->whereNull('lab_cases.deleted_at')
            ->whereIn('lab_cases.status', [LabCase::STATUS_SENT, LabCase::STATUS_IN_PRODUCTION])
            ->whereNotNull('lab_cases.expected_return_date')
            ->where('lab_cases.expected_return_date', '<', now()->format('Y-m-d'))
            ->whereNull('lab_cases.actual_return_date')
            ->select(
                'lab_cases.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'labs.name as lab_name'
            )
            ->orderBy('lab_cases.expected_return_date')
            ->get();
    }

    /**
     * Create a lab case.
     */
    public function createLabCase(array $data): LabCase
    {
        $data['lab_case_no'] = LabCase::generateCaseNo();
        $data['_who_added'] = Auth::id();

        return LabCase::create($data);
    }

    /**
     * Update a lab case.
     */
    public function updateLabCase(int $id, array $data): bool
    {
        return (bool) LabCase::where('id', $id)->update($data);
    }

    /**
     * Update lab case status with side effects.
     */
    public function updateStatus(int $id, string $status, array $extra = []): bool
    {
        $update = ['status' => $status];

        if ($status === LabCase::STATUS_SENT && empty($extra['sent_date'])) {
            $update['sent_date'] = now()->format('Y-m-d');
        }

        if (in_array($status, [LabCase::STATUS_RETURNED, LabCase::STATUS_TRY_IN, LabCase::STATUS_COMPLETED]) && empty($extra['actual_return_date'])) {
            $update['actual_return_date'] = now()->format('Y-m-d');
        }

        if ($status === LabCase::STATUS_REWORK) {
            $case = LabCase::find($id);
            if ($case) {
                $update['rework_count'] = $case->rework_count + 1;
                $update['actual_return_date'] = null;
            }
            if (!empty($extra['rework_reason'])) {
                $update['rework_reason'] = $extra['rework_reason'];
            }
        }

        return (bool) LabCase::where('id', $id)->update(array_merge($update, $extra));
    }

    /**
     * Delete a lab case (soft).
     */
    public function deleteLabCase(int $id): bool
    {
        return (bool) LabCase::where('id', $id)->delete();
    }

    /**
     * Get a single lab case with relationships.
     */
    public function getLabCase(int $id): ?LabCase
    {
        return LabCase::with(['patient', 'doctor', 'lab', 'appointment'])->find($id);
    }

    /**
     * Get lab cases for a patient.
     */
    public function getPatientCases(int $patientId): Collection
    {
        return LabCase::with(['lab', 'doctor'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get lab case statistics.
     */
    public function getStatistics(): array
    {
        $stats = DB::table('lab_cases')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rework,
                SUM(lab_fee) as total_lab_fee,
                SUM(patient_charge) as total_patient_charge,
                SUM(patient_charge - lab_fee) as total_profit
            ", [
                LabCase::STATUS_PENDING, LabCase::STATUS_SENT, LabCase::STATUS_IN_PRODUCTION,
                LabCase::STATUS_COMPLETED,
                LabCase::STATUS_REWORK,
            ])
            ->first();

        $overdue = DB::table('lab_cases')
            ->whereNull('deleted_at')
            ->whereIn('status', [LabCase::STATUS_SENT, LabCase::STATUS_IN_PRODUCTION])
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now()->format('Y-m-d'))
            ->whereNull('actual_return_date')
            ->count();

        return [
            'total'                => (int) ($stats->total ?? 0),
            'active'               => (int) ($stats->active ?? 0),
            'completed'            => (int) ($stats->completed ?? 0),
            'rework'               => (int) ($stats->rework ?? 0),
            'overdue'              => $overdue,
            'total_lab_fee'        => (float) ($stats->total_lab_fee ?? 0),
            'total_patient_charge' => (float) ($stats->total_patient_charge ?? 0),
            'total_profit'         => (float) ($stats->total_profit ?? 0),
        ];
    }

    /**
     * Get print data for a lab case.
     */
    public function getPrintData(int $id): ?LabCase
    {
        return LabCase::with(['patient', 'doctor', 'lab', 'appointment', 'addedBy'])->find($id);
    }

    // ─── DataTable formatting ────────────────────────────────────

    /**
     * Build DataTables response for the lab cases index page.
     */
    public function buildIndexDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('lab_case_no', function ($row) {
                return '<a href="' . url('lab-cases/' . $row->id) . '">' . e($row->lab_case_no) . '</a>';
            })
            ->addColumn('patient_name', function ($row) {
                return $row->patient_name ?? '-';
            })
            ->addColumn('doctor_name', function ($row) {
                return $row->doctor_name ?? '-';
            })
            ->addColumn('lab_name', function ($row) {
                return $row->lab_name ?? '-';
            })
            ->addColumn('prosthesis_type_label', function ($row) {
                return __('lab_cases.type_' . $row->prosthesis_type);
            })
            ->addColumn('status_label', function ($row) {
                $badges = [
                    LabCase::STATUS_PENDING       => 'default',
                    LabCase::STATUS_SENT          => 'info',
                    LabCase::STATUS_IN_PRODUCTION => 'warning',
                    LabCase::STATUS_RETURNED      => 'primary',
                    LabCase::STATUS_TRY_IN        => 'info',
                    LabCase::STATUS_COMPLETED     => 'success',
                    LabCase::STATUS_REWORK        => 'danger',
                ];
                $badge = $badges[$row->status] ?? 'default';
                return '<span class="label label-' . $badge . '">' . __('lab_cases.status_' . $row->status) . '</span>';
            })
            ->addColumn('overdue_flag', function ($row) {
                if (!empty($row->expected_return_date)
                    && in_array($row->status, [LabCase::STATUS_SENT, LabCase::STATUS_IN_PRODUCTION])
                    && empty($row->actual_return_date)
                    && $row->expected_return_date < now()->format('Y-m-d')
                ) {
                    return '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i></span>';
                }
                return '';
            })
            ->addColumn('action', function ($row) {
                return '
                <div class="btn-group">
                    <button class="btn blue dropdown-toggle btn-sm" type="button" data-toggle="dropdown" aria-expanded="false">
                        ' . __('common.action') . ' <i class="fa fa-angle-down"></i>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="' . url('lab-cases/' . $row->id) . '">' . __('lab_cases.view_lab_case') . '</a></li>
                        <li><a href="#" onclick="editLabCase(' . $row->id . ')">' . __('lab_cases.edit_lab_case') . '</a></li>
                        <li><a href="#" onclick="updateStatus(' . $row->id . ')">' . __('lab_cases.update_status') . '</a></li>
                        <li><a href="' . url('print-lab-case/' . $row->id) . '" target="_blank">' . __('lab_cases.print_lab_case') . '</a></li>
                        <li class="divider"></li>
                        <li><a href="#" onclick="deleteLabCase(' . $row->id . ')" class="text-danger">' . __('lab_cases.delete') . '</a></li>
                    </ul>
                </div>';
            })
            ->rawColumns(['lab_case_no', 'status_label', 'overdue_flag', 'action'])
            ->make(true);
    }

    /**
     * Build DataTables response for a patient's lab cases.
     */
    public function buildPatientLabCasesDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('lab_case_no', function ($row) {
                return '<a href="' . url('lab-cases/' . $row->id) . '">' . e($row->lab_case_no) . '</a>';
            })
            ->addColumn('status_label', function ($row) {
                $badges = [
                    LabCase::STATUS_PENDING       => 'default',
                    LabCase::STATUS_SENT          => 'info',
                    LabCase::STATUS_IN_PRODUCTION => 'warning',
                    LabCase::STATUS_RETURNED      => 'primary',
                    LabCase::STATUS_TRY_IN        => 'info',
                    LabCase::STATUS_COMPLETED     => 'success',
                    LabCase::STATUS_REWORK        => 'danger',
                ];
                $badge = $badges[$row->status] ?? 'default';
                return '<span class="label label-' . $badge . '">' . __('lab_cases.status_' . $row->status) . '</span>';
            })
            ->rawColumns(['lab_case_no', 'status_label'])
            ->make(true);
    }
}
