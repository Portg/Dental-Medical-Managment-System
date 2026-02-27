<?php

namespace App\Services;

use App\Appointment;
use App\Http\Helper\NameHelper;
use App\WaitingQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class TodayWorkService
{
    /**
     * Get KPI data for the today-work page.
     * Financial amounts are returned in thousands (divided by 1000).
     */
    public function getKpi(int $branchId, ?string $date = null): array
    {
        $today = $date ?? date('Y-m-d');

        // 今日就诊人数：in_treatment or completed in waiting_queues today
        $todayPatients = DB::table('waiting_queues')
            ->whereDate('check_in_time', $today)
            ->whereIn('status', [WaitingQueue::STATUS_IN_TREATMENT, WaitingQueue::STATUS_COMPLETED])
            ->whereNull('deleted_at')
            ->distinct('patient_id')
            ->count('patient_id');

        // 今日出诊人数：distinct doctors with appointments today
        $todayDoctors = DB::table('appointments')
            ->where('start_date', $today)
            ->whereNull('deleted_at')
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->distinct('doctor_id')
            ->count('doctor_id');

        // 今日回访人数：revisit appointments that have checked in today
        $todayRevisits = DB::table('appointments as a')
            ->join('waiting_queues as wq', function ($join) use ($today) {
                $join->on('wq.appointment_id', '=', 'a.id')
                    ->whereDate('wq.check_in_time', $today)
                    ->whereNotIn('wq.status', [WaitingQueue::STATUS_CANCELLED, WaitingQueue::STATUS_NO_SHOW])
                    ->whereNull('wq.deleted_at');
            })
            ->where('a.appointment_type', 'revisit')
            ->where('a.start_date', $today)
            ->whereNull('a.deleted_at')
            ->distinct('a.patient_id')
            ->count('a.patient_id');

        // 今日预约人数
        $todayAppointments = DB::table('appointments')
            ->where('start_date', $today)
            ->whereNull('deleted_at')
            ->count();

        // 今日应收金额（千元）：invoices created today
        $todayReceivable = (float) DB::table('invoices')
            ->whereDate('created_at', $today)
            ->whereNull('deleted_at')
            ->sum('total_amount');

        // 今日实收金额（千元）：payments received today
        $todayCollected = (float) DB::table('invoice_payments')
            ->where('payment_date', $today)
            ->whereNull('deleted_at')
            ->sum('amount');

        return [
            'today_patients'     => $todayPatients,
            'today_doctors'      => $todayDoctors,
            'today_revisits'     => $todayRevisits,
            'today_appointments' => $todayAppointments,
            'today_receivable'   => round($todayReceivable / 1000, 1),
            'today_collected'    => round($todayCollected / 1000, 1),
        ];
    }

    /**
     * Get workflow status counts for the status cards.
     */
    public function getStats(int $branchId, ?string $date = null, ?int $doctorId = null): array
    {
        $today = $date ?? date('Y-m-d');

        // All today's appointments
        $query = DB::table('appointments as a')
            ->leftJoin('waiting_queues as wq', function ($join) use ($today) {
                $join->on('wq.appointment_id', '=', 'a.id')
                    ->whereDate('wq.check_in_time', $today)
                    ->whereNotIn('wq.status', [WaitingQueue::STATUS_CANCELLED, WaitingQueue::STATUS_NO_SHOW])
                    ->whereNull('wq.deleted_at');
            })
            ->where('a.start_date', $today)
            ->whereNull('a.deleted_at')
            ->select('a.status as apt_status', 'wq.status as queue_status');

        if ($doctorId) {
            $query->where('a.doctor_id', $doctorId);
        }

        $appointments = $query->get();

        $counts = [
            'not_arrived'  => 0,
            'waiting'      => 0,
            'called'       => 0,
            'in_treatment' => 0,
            'completed'    => 0,
            'no_show'      => 0,
        ];

        foreach ($appointments as $row) {
            $status = $this->resolveDisplayStatus($row->apt_status, $row->queue_status);
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * Get kanban board data — all today's patients grouped by display status.
     */
    public function getKanbanData(int $branchId, ?string $date = null, ?int $doctorId = null): array
    {
        $today = $date ?? date('Y-m-d');

        $query = DB::table('appointments as a')
            ->leftJoin('waiting_queues as wq', function ($join) use ($today) {
                $join->on('wq.appointment_id', '=', 'a.id')
                    ->whereDate('wq.check_in_time', $today)
                    ->whereNotIn('wq.status', [WaitingQueue::STATUS_CANCELLED, WaitingQueue::STATUS_NO_SHOW])
                    ->whereNull('wq.deleted_at');
            })
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->join('users as d', 'd.id', '=', 'a.doctor_id')
            ->leftJoin('medical_services as ms', 'ms.id', '=', 'a.service_id')
            ->where('a.start_date', $today)
            ->whereNull('a.deleted_at');

        if ($doctorId) {
            $query->where('a.doctor_id', $doctorId);
        }

        $rows = $query->select(
                'a.id as appointment_id',
                'a.start_time',
                'a.status as apt_status',
                'a.patient_id',
                'a.doctor_id',
                'a.appointment_type',
                'p.surname as p_surname',
                'p.othername as p_othername',
                'p.phone_no',
                'd.surname as d_surname',
                'd.othername as d_othername',
                'ms.name as service_name',
                'wq.id as queue_id',
                'wq.queue_number',
                'wq.status as queue_status',
                'wq.check_in_time'
            )
            ->orderBy('a.sort_by')
            ->get();

        $columns = [
            'not_arrived'  => [],
            'waiting'      => [],
            'called'       => [],
            'in_treatment' => [],
            'completed'    => [],
            'no_show'      => [],
        ];

        foreach ($rows as $row) {
            $status = $this->resolveDisplayStatus($row->apt_status, $row->queue_status);
            if (!isset($columns[$status])) {
                continue;
            }

            $columns[$status][] = [
                'appointment_id'   => $row->appointment_id,
                'queue_id'         => $row->queue_id,
                'patient_id'       => $row->patient_id,
                'patient_name'     => NameHelper::join($row->p_surname, $row->p_othername),
                'patient_phone'    => $row->phone_no ? substr($row->phone_no, 0, 3) . '****' . substr($row->phone_no, -4) : '',
                'doctor_name'      => NameHelper::join($row->d_surname, $row->d_othername),
                'service'          => $row->service_name ?? '',
                'start_time'       => $row->start_time ? date('H:i', strtotime($row->start_time)) : '',
                'check_in_time'    => $row->check_in_time,
                'appointment_type' => $row->appointment_type,
            ];
        }

        return $columns;
    }

    /**
     * Get the unified today-work query for DataTables.
     */
    public function getTodayWorkQuery(int $branchId, ?string $statusFilter, ?string $search, ?string $date = null, ?int $doctorId = null): \Illuminate\Database\Query\Builder
    {
        $today = $date ?? date('Y-m-d');

        $query = DB::table('appointments as a')
            ->leftJoin('waiting_queues as wq', function ($join) use ($today) {
                $join->on('wq.appointment_id', '=', 'a.id')
                    ->whereDate('wq.check_in_time', $today)
                    ->whereNotIn('wq.status', [WaitingQueue::STATUS_CANCELLED, WaitingQueue::STATUS_NO_SHOW])
                    ->whereNull('wq.deleted_at');
            })
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->join('users as d', 'd.id', '=', 'a.doctor_id')
            ->leftJoin('medical_services as ms', 'ms.id', '=', 'a.service_id')
            ->where('a.start_date', $today)
            ->whereNull('a.deleted_at')
            ->select(
                'a.id as appointment_id',
                'a.start_time',
                'a.status as apt_status',
                'a.patient_id',
                'a.doctor_id',
                'a.appointment_type',
                'p.surname as p_surname',
                'p.othername as p_othername',
                'p.phone_no',
                'd.surname as d_surname',
                'd.othername as d_othername',
                'ms.name as service_name',
                'wq.id as queue_id',
                'wq.queue_number',
                'wq.status as queue_status',
                'wq.check_in_time',
                'wq.chair_id',
                DB::raw('a.sort_by as sort_key')
            );

        // Doctor filter
        if ($doctorId) {
            $query->where('a.doctor_id', $doctorId);
        }

        // Status filter
        if ($statusFilter && $statusFilter !== 'all') {
            switch ($statusFilter) {
                case 'not_arrived':
                    $query->whereNull('wq.id')
                        ->where('a.status', '!=', Appointment::STATUS_NO_SHOW);
                    break;
                case 'waiting':
                    $query->where('wq.status', WaitingQueue::STATUS_WAITING);
                    break;
                case 'called':
                    $query->where('wq.status', WaitingQueue::STATUS_CALLED);
                    break;
                case 'in_treatment':
                    $query->where('wq.status', WaitingQueue::STATUS_IN_TREATMENT);
                    break;
                case 'completed':
                    $query->where('wq.status', WaitingQueue::STATUS_COMPLETED);
                    break;
                case 'no_show':
                    $query->where('a.status', Appointment::STATUS_NO_SHOW);
                    break;
            }
        }

        // Patient search
        if ($search) {
            $query->where(function ($q) use ($search) {
                NameHelper::addNameSearch($q, $search, 'p');
                $q->orWhere('p.phone_no', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('a.sort_by');

        return $query;
    }

    /**
     * Build the DataTable response.
     */
    public function buildDataTable($query)
    {
        return DataTables::of($query)
            ->addColumn('patient_name', function ($row) {
                return NameHelper::join($row->p_surname, $row->p_othername);
            })
            ->addColumn('patient_phone', function ($row) {
                $phone = $row->phone_no ?? '';
                if (strlen($phone) >= 11) {
                    return substr($phone, 0, 3) . '****' . substr($phone, -4);
                }
                return $phone;
            })
            ->addColumn('doctor_name', function ($row) {
                return NameHelper::join($row->d_surname, $row->d_othername);
            })
            ->addColumn('service', function ($row) {
                return $row->service_name ?? '-';
            })
            ->addColumn('display_status', function ($row) {
                $status = $this->resolveDisplayStatus($row->apt_status, $row->queue_status);
                return $this->renderStatusBadge($status);
            })
            ->addColumn('action', function ($row) {
                return $this->renderActions($row);
            })
            ->editColumn('start_time', function ($row) {
                if (!$row->start_time) return '-';
                return date('H:i', strtotime($row->start_time));
            })
            ->rawColumns(['display_status', 'action'])
            ->make(true);
    }

    /**
     * Resolve the unified display status from appointment + queue status.
     */
    public function resolveDisplayStatus(?string $aptStatus, ?string $queueStatus): string
    {
        if ($aptStatus === Appointment::STATUS_NO_SHOW) {
            return 'no_show';
        }
        if ($queueStatus === null) {
            return 'not_arrived';
        }
        return match ($queueStatus) {
            WaitingQueue::STATUS_WAITING      => 'waiting',
            WaitingQueue::STATUS_CALLED        => 'called',
            WaitingQueue::STATUS_IN_TREATMENT  => 'in_treatment',
            WaitingQueue::STATUS_COMPLETED     => 'completed',
            default                            => 'not_arrived',
        };
    }

    private function renderStatusBadge(string $status): string
    {
        $badges = [
            'not_arrived'  => ['warning', 'today_work.not_arrived'],
            'waiting'      => ['info', 'today_work.waiting'],
            'called'       => ['primary', 'today_work.called'],
            'in_treatment' => ['success', 'today_work.in_treatment'],
            'completed'    => ['default', 'today_work.completed'],
            'no_show'      => ['danger', 'today_work.no_show'],
        ];

        $badge = $badges[$status] ?? ['default', $status];
        return '<span class="label label-' . $badge[0] . '">' . __($badge[1]) . '</span>';
    }

    private function renderActions($row): string
    {
        $status = $this->resolveDisplayStatus($row->apt_status, $row->queue_status);
        $actions = '';

        switch ($status) {
            case 'not_arrived':
                $actions .= '<button class="btn btn-xs btn-success tw-action-btn" onclick="quickCheckIn(' . $row->appointment_id . ')">'
                    . '<i class="fa fa-sign-in"></i> ' . __('today_work.check_in') . '</button> ';
                $actions .= '<button class="btn btn-xs btn-danger tw-action-btn" onclick="quickNoShow(' . $row->appointment_id . ')">'
                    . '<i class="fa fa-times"></i> ' . __('today_work.mark_no_show') . '</button>';
                break;

            case 'waiting':
                $actions .= '<button class="btn btn-xs btn-info tw-action-btn" onclick="quickCall(' . $row->queue_id . ')">'
                    . '<i class="fa fa-bullhorn"></i> ' . __('today_work.call') . '</button> ';
                $actions .= '<button class="btn btn-xs btn-danger tw-action-btn" onclick="quickCancelQueue(' . $row->queue_id . ')">'
                    . '<i class="fa fa-times"></i> ' . __('common.cancel') . '</button>';
                break;

            case 'called':
                $actions .= '<button class="btn btn-xs btn-primary tw-action-btn" onclick="quickStartTreatment(' . $row->queue_id . ')">'
                    . '<i class="fa fa-play"></i> ' . __('today_work.start_treatment') . '</button> ';
                $actions .= '<button class="btn btn-xs btn-info tw-action-btn" onclick="quickCall(' . $row->queue_id . ')">'
                    . '<i class="fa fa-bullhorn"></i> ' . __('today_work.recall') . '</button>';
                break;

            case 'in_treatment':
                $actions .= '<div class="tw-quick-actions">';
                $actions .= '<button class="btn btn-xs btn-default tw-action-btn" onclick="quickMedicalCase(' . $row->patient_id . ',' . $row->appointment_id . ')">'
                    . '<i class="fa fa-file-text-o"></i> ' . __('today_work.medical_case') . '</button> ';
                $actions .= '<button class="btn btn-xs btn-default tw-action-btn" onclick="quickPrescription(' . $row->appointment_id . ')">'
                    . '<i class="fa fa-medkit"></i> ' . __('today_work.prescription') . '</button> ';
                $actions .= '<button class="btn btn-xs btn-default tw-action-btn" onclick="quickInvoice(' . $row->appointment_id . ')">'
                    . '<i class="fa fa-money"></i> ' . __('today_work.invoice') . '</button> ';
                $actions .= '<button class="btn btn-xs btn-default tw-action-btn" onclick="quickNextAppointment(' . $row->patient_id . ')">'
                    . '<i class="fa fa-calendar-plus-o"></i> ' . __('today_work.next_appointment') . '</button> ';
                $actions .= '<button class="btn btn-xs btn-success tw-action-btn" onclick="quickCompleteTreatment(' . $row->queue_id . ')">'
                    . '<i class="fa fa-check"></i> ' . __('today_work.complete_treatment') . '</button>';
                $actions .= '</div>';
                break;

            case 'completed':
                $actions .= '<a class="btn btn-xs btn-default" href="' . url('medical-treatment/' . $row->appointment_id) . '">'
                    . '<i class="fa fa-eye"></i> ' . __('common.view') . '</a>';
                break;
        }

        return $actions;
    }

    /**
     * 今日对账 — 按支付方式汇总今日收款
     */
    public function getTodayBilling(int $branchId, ?string $date = null): array
    {
        $today = $date ?? date('Y-m-d');

        // 按支付方式分组汇总
        $byMethod = DB::table('invoice_payments')
            ->where('payment_date', $today)
            ->whereNull('deleted_at')
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        $totalAmount = $byMethod->sum('total');
        $totalCount = $byMethod->sum('count');

        return [
            'by_method' => $byMethod->map(function ($item) {
                return [
                    'method' => $item->payment_method ?? 'Unknown',
                    'total'  => round((float) $item->total, 2),
                    'count'  => (int) $item->count,
                ];
            })->values()->toArray(),
            'total_amount' => round($totalAmount, 2),
            'total_count'  => $totalCount,
        ];
    }

    /**
     * 今日回访 — 今日应回访 + 已完成回访
     */
    public function getTodayFollowups(int $branchId, ?string $date = null, ?string $status = null, ?string $search = null, ?int $doctorId = null, ?string $followupType = null): array
    {
        $today = $date ?? date('Y-m-d');

        $followups = DB::table('patient_followups as f')
            ->join('patients as p', 'p.id', '=', 'f.patient_id')
            ->leftJoin('appointments as apt', 'apt.id', '=', 'f.appointment_id')
            ->leftJoin('users as d', 'd.id', '=', DB::raw('COALESCE(apt.doctor_id, f._who_added)'))
            ->whereDate('f.scheduled_date', $today)
            ->whereNull('f.deleted_at')
            ->when($status, function ($q) use ($status) {
                $q->where('f.status', $status);
            })
            ->when($followupType, function ($q) use ($followupType) {
                $q->where('f.followup_type', $followupType);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    NameHelper::addNameSearch($sq, $search, 'p');
                    $sq->orWhere('p.phone_no', 'like', '%' . $search . '%')
                       ->orWhere('p.patient_no', 'like', '%' . $search . '%');
                });
            })
            ->when($doctorId, function ($q) use ($doctorId) {
                $q->where(function ($sq) use ($doctorId) {
                    $sq->where('d.id', $doctorId);
                });
            })
            ->select(
                'f.id',
                'f.followup_type',
                'f.status',
                'f.purpose',
                'f.scheduled_date',
                'f.completed_date',
                'p.id as patient_id',
                'p.surname',
                'p.othername',
                'p.phone_no',
                'd.surname as d_surname',
                'd.othername as d_othername'
            )
            ->orderBy('f.status')  // Pending first
            ->orderBy('f.scheduled_date')
            ->get();

        return $followups->map(function ($row) {
            return [
                'id'             => $row->id,
                'patient_id'     => $row->patient_id,
                'patient_name'   => NameHelper::join($row->surname, $row->othername),
                'patient_phone'  => $row->phone_no ? substr($row->phone_no, 0, 3) . '****' . substr($row->phone_no, -4) : '',
                'followup_type'  => $row->followup_type,
                'status'         => $row->status,
                'purpose'        => $row->purpose ?? '',
                'scheduled_date' => $row->scheduled_date,
                'completed_date' => $row->completed_date,
                'doctor_name'    => NameHelper::join($row->d_surname ?? '', $row->d_othername ?? ''),
            ];
        })->toArray();
    }

    /**
     * 明日预约列表
     */
    public function getTomorrowAppointments(int $branchId, ?string $date = null, ?int $doctorId = null, ?string $search = null): array
    {
        $baseDate = $date ?? date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($baseDate . ' +1 day'));

        $rows = DB::table('appointments as a')
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->join('users as d', 'd.id', '=', 'a.doctor_id')
            ->leftJoin('medical_services as ms', 'ms.id', '=', 'a.service_id')
            ->where('a.start_date', $tomorrow)
            ->whereNull('a.deleted_at')
            ->whereNotIn('a.status', [Appointment::STATUS_CANCELLED])
            ->when($doctorId, function ($q) use ($doctorId) {
                $q->where('a.doctor_id', $doctorId);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    NameHelper::addNameSearch($sq, $search, 'p');
                    $sq->orWhere('p.phone_no', 'like', '%' . $search . '%')
                       ->orWhere('p.patient_no', 'like', '%' . $search . '%');
                });
            })
            ->select(
                'a.id',
                'a.start_time',
                'a.appointment_type',
                'a.status',
                'p.id as patient_id',
                'p.surname as p_surname',
                'p.othername as p_othername',
                'p.phone_no',
                'd.surname as d_surname',
                'd.othername as d_othername',
                'ms.name as service_name'
            )
            ->orderBy('a.start_time')
            ->get();

        return $rows->map(function ($row) {
            return [
                'id'               => $row->id,
                'patient_id'       => $row->patient_id,
                'patient_name'     => NameHelper::join($row->p_surname, $row->p_othername),
                'patient_phone'    => $row->phone_no ? substr($row->phone_no, 0, 3) . '****' . substr($row->phone_no, -4) : '',
                'doctor_name'      => NameHelper::join($row->d_surname, $row->d_othername),
                'service'          => $row->service_name ?? '',
                'start_time'       => $row->start_time ? date('H:i', strtotime($row->start_time)) : '',
                'appointment_type' => $row->appointment_type,
            ];
        })->toArray();
    }

    /**
     * 一周失约 — 最近7天有预约但未到诊的患者
     */
    public function getWeekMissed(int $branchId, ?string $startDate = null, ?string $endDate = null): array
    {
        $end = $endDate ?? date('Y-m-d');
        $start = $startDate ?? date('Y-m-d', strtotime($end . ' -7 days'));

        $rows = DB::table('appointments as a')
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->join('users as d', 'd.id', '=', 'a.doctor_id')
            ->leftJoin('medical_services as ms', 'ms.id', '=', 'a.service_id')
            ->where('a.start_date', '>=', $start)
            ->where('a.start_date', '<=', $end)
            ->where('a.status', Appointment::STATUS_NO_SHOW)
            ->whereNull('a.deleted_at')
            ->select(
                'a.id',
                'a.start_date',
                'a.start_time',
                'p.id as patient_id',
                'p.surname as p_surname',
                'p.othername as p_othername',
                'p.phone_no',
                'd.surname as d_surname',
                'd.othername as d_othername',
                'ms.name as service_name'
            )
            ->orderByDesc('a.start_date')
            ->get();

        return $rows->map(function ($row) {
            return [
                'id'            => $row->id,
                'patient_id'    => $row->patient_id,
                'patient_name'  => NameHelper::join($row->p_surname, $row->p_othername),
                'patient_phone' => $row->phone_no ? substr($row->phone_no, 0, 3) . '****' . substr($row->phone_no, -4) : '',
                'doctor_name'   => NameHelper::join($row->d_surname, $row->d_othername),
                'service'       => $row->service_name ?? '',
                'date'          => $row->start_date,
                'time'          => $row->start_time ? date('H:i', strtotime($row->start_time)) : '',
            ];
        })->toArray();
    }

    /**
     * 今日生日患者列表
     */
    public function getTodayBirthdays(int $branchId, ?string $date = null): array
    {
        $today = $date ?? date('Y-m-d');
        $monthDay = date('m-d', strtotime($today));

        $rows = DB::table('patients')
            ->whereNull('deleted_at')
            ->whereRaw('DATE_FORMAT(date_of_birth, "%m-%d") = ?', [$monthDay])
            ->select('id', 'surname', 'othername', 'phone_no', 'date_of_birth', 'gender')
            ->orderBy('surname')
            ->get();

        return $rows->map(function ($row) use ($today) {
            $age = $row->date_of_birth ? (date('Y', strtotime($today)) - date('Y', strtotime($row->date_of_birth))) : null;
            return [
                'id'       => $row->id,
                'name'     => NameHelper::join($row->surname, $row->othername),
                'phone'    => $row->phone_no ? substr($row->phone_no, 0, 3) . '****' . substr($row->phone_no, -4) : '',
                'gender'   => $row->gender,
                'age'      => $age,
            ];
        })->toArray();
    }

    /**
     * 今日已收款 — payments received on the given date
     */
    public function getTodayPayments(int $branchId, ?string $date = null): array
    {
        $today = $date ?? date('Y-m-d');

        $rows = DB::table('invoice_payments as ip')
            ->join('invoices as inv', 'inv.id', '=', 'ip.invoice_id')
            ->join('patients as p', 'p.id', '=', 'inv.patient_id')
            ->where('ip.payment_date', $today)
            ->whereNull('ip.deleted_at')
            ->whereNull('inv.deleted_at')
            ->select(
                'ip.id',
                'ip.amount',
                'ip.payment_method',
                'inv.invoice_no',
                'p.id as patient_id',
                'p.surname',
                'p.othername',
                'p.phone_no'
            )
            ->orderByDesc('ip.created_at')
            ->get();

        $totalAmount = $rows->sum('amount');

        return [
            'items' => $rows->map(function ($row) {
                return [
                    'id'             => $row->id,
                    'patient_id'     => $row->patient_id,
                    'patient_name'   => NameHelper::join($row->surname, $row->othername),
                    'patient_phone'  => $row->phone_no ? substr($row->phone_no, 0, 3) . '****' . substr($row->phone_no, -4) : '',
                    'payment_method' => $row->payment_method ?? '',
                    'amount'         => round((float) $row->amount, 2),
                    'invoice_no'     => $row->invoice_no ?? '',
                ];
            })->toArray(),
            'total_amount' => round($totalAmount, 2),
            'total_count'  => $rows->count(),
        ];
    }

    /**
     * 今日待收款 — invoices billed today that are unpaid or partial
     */
    public function getTodayUnpaid(int $branchId, ?string $date = null): array
    {
        $today = $date ?? date('Y-m-d');

        $rows = DB::table('invoices as inv')
            ->join('patients as p', 'p.id', '=', 'inv.patient_id')
            ->whereDate('inv.invoice_date', $today)
            ->whereIn('inv.payment_status', ['unpaid', 'partial'])
            ->whereNull('inv.deleted_at')
            ->select(
                'inv.id',
                'inv.invoice_no',
                'inv.total_amount',
                'inv.paid_amount',
                'inv.outstanding_amount',
                'inv.payment_status',
                'p.id as patient_id',
                'p.surname',
                'p.othername',
                'p.phone_no'
            )
            ->orderByDesc('inv.created_at')
            ->get();

        $totalOutstanding = $rows->sum('outstanding_amount');

        return [
            'items' => $rows->map(function ($row) {
                return [
                    'id'                 => $row->id,
                    'patient_id'         => $row->patient_id,
                    'patient_name'       => NameHelper::join($row->surname, $row->othername),
                    'patient_phone'      => $row->phone_no ? substr($row->phone_no, 0, 3) . '****' . substr($row->phone_no, -4) : '',
                    'total_amount'       => round((float) $row->total_amount, 2),
                    'paid_amount'        => round((float) $row->paid_amount, 2),
                    'outstanding_amount' => round((float) $row->outstanding_amount, 2),
                    'invoice_no'         => $row->invoice_no ?? '',
                    'payment_status'     => $row->payment_status,
                ];
            })->toArray(),
            'total_outstanding' => round($totalOutstanding, 2),
            'total_count'       => $rows->count(),
        ];
    }

    /**
     * 外加工查询 — lab cases expected to return or actually returned on the given date
     */
    public function getLabCases(int $branchId, ?string $date = null): array
    {
        $today = $date ?? date('Y-m-d');

        $rows = DB::table('lab_cases as lc')
            ->join('patients as p', 'p.id', '=', 'lc.patient_id')
            ->join('users as d', 'd.id', '=', 'lc.doctor_id')
            ->leftJoin('labs as l', 'l.id', '=', 'lc.lab_id')
            ->where(function ($q) use ($today) {
                $q->where('lc.expected_return_date', $today)
                  ->orWhere('lc.actual_return_date', $today);
            })
            ->whereNull('lc.deleted_at')
            ->select(
                'lc.id',
                'lc.lab_case_no',
                'lc.prosthesis_type',
                'lc.material',
                'lc.status',
                'lc.expected_return_date',
                'lc.actual_return_date',
                'p.id as patient_id',
                'p.surname as p_surname',
                'p.othername as p_othername',
                'd.surname as d_surname',
                'd.othername as d_othername',
                'l.name as lab_name'
            )
            ->orderBy('lc.expected_return_date')
            ->get();

        return $rows->map(function ($row) {
            return [
                'id'                   => $row->id,
                'lab_case_no'          => $row->lab_case_no,
                'patient_id'           => $row->patient_id,
                'patient_name'         => NameHelper::join($row->p_surname, $row->p_othername),
                'doctor_name'          => NameHelper::join($row->d_surname, $row->d_othername),
                'prosthesis_type'      => $row->prosthesis_type ?? '',
                'material'             => $row->material ?? '',
                'lab_name'             => $row->lab_name ?? '',
                'status'               => $row->status,
                'expected_return_date' => $row->expected_return_date,
                'actual_return_date'   => $row->actual_return_date,
            ];
        })->toArray();
    }

    /**
     * Get doctor IDs that have schedules on a given date.
     *
     * Checks both non-recurring schedules (exact date match) and recurring
     * schedules (daily, weekly patterns).
     */
    public function getScheduledDoctorIds(string $date): array
    {
        // Non-recurring schedules: exact date match
        $nonRecurring = DB::table('doctor_schedules')
            ->where('schedule_date', $date)
            ->where(function ($q) {
                $q->where('is_recurring', 0)
                  ->orWhereNull('is_recurring');
            })
            ->whereNull('deleted_at')
            ->pluck('doctor_id')
            ->toArray();

        // Recurring schedules: schedule_date <= date AND (recurring_until IS NULL OR recurring_until >= date)
        $recurringCandidates = DB::table('doctor_schedules')
            ->where('is_recurring', 1)
            ->where('schedule_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('recurring_until')
                  ->orWhere('recurring_until', '>=', $date);
            })
            ->whereNull('deleted_at')
            ->select('doctor_id', 'schedule_date', 'recurring_pattern')
            ->get();

        $recurring = [];
        $targetDayOfWeek = date('w', strtotime($date));

        foreach ($recurringCandidates as $schedule) {
            $pattern = $schedule->recurring_pattern;

            if ($pattern === 'daily') {
                // Daily: all dates from schedule_date onwards match
                $recurring[] = $schedule->doctor_id;
            } elseif ($pattern === 'weekly') {
                // Weekly: check if day_of_week matches
                $scheduleDayOfWeek = date('w', strtotime($schedule->schedule_date));
                if ($scheduleDayOfWeek === $targetDayOfWeek) {
                    $recurring[] = $schedule->doctor_id;
                }
            }
        }

        return array_values(array_unique(array_merge($nonRecurring, $recurring)));
    }

    /**
     * 医生表视图 — 按医生分组显示今日患者
     */
    public function getDoctorTable(int $branchId, ?string $date = null, ?int $doctorId = null): array
    {
        $today = $date ?? date('Y-m-d');

        // 1. Get scheduled doctor IDs from doctor_schedules
        $scheduledDoctorIds = $this->getScheduledDoctorIds($today);
        if ($doctorId) {
            $scheduledDoctorIds = array_intersect($scheduledDoctorIds, [$doctorId]);
        }

        // 2. Pre-populate doctors from schedules (so they appear even with zero patients)
        $doctors = [];
        if (!empty($scheduledDoctorIds)) {
            $scheduledUsers = DB::table('users')
                ->whereIn('id', $scheduledDoctorIds)
                ->whereNull('deleted_at')
                ->select('id', 'surname', 'othername')
                ->orderBy('surname')
                ->get();

            foreach ($scheduledUsers as $user) {
                $doctors[$user->id] = [
                    'doctor_id'    => $user->id,
                    'doctor_name'  => NameHelper::join($user->surname, $user->othername),
                    'total'        => 0,
                    'waiting'      => 0,
                    'in_treatment' => 0,
                    'completed'    => 0,
                    'patients'     => [],
                ];
            }
        }

        // 3. Query appointments for the date
        $rows = DB::table('appointments as a')
            ->leftJoin('waiting_queues as wq', function ($join) use ($today) {
                $join->on('wq.appointment_id', '=', 'a.id')
                    ->whereDate('wq.check_in_time', $today)
                    ->whereNotIn('wq.status', [WaitingQueue::STATUS_CANCELLED, WaitingQueue::STATUS_NO_SHOW])
                    ->whereNull('wq.deleted_at');
            })
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->join('users as d', 'd.id', '=', 'a.doctor_id')
            ->leftJoin('medical_services as ms', 'ms.id', '=', 'a.service_id')
            ->where('a.start_date', $today)
            ->whereNull('a.deleted_at')
            ->when($doctorId, function ($q) use ($doctorId) {
                $q->where('a.doctor_id', $doctorId);
            })
            ->select(
                'a.id as appointment_id',
                'a.start_time',
                'a.status as apt_status',
                'a.patient_id',
                'a.doctor_id',
                'p.surname as p_surname',
                'p.othername as p_othername',
                'd.surname as d_surname',
                'd.othername as d_othername',
                'ms.name as service_name',
                'wq.status as queue_status',
                'wq.check_in_time'
            )
            ->orderBy('d.surname')
            ->orderBy('a.sort_by')
            ->get();

        // 4. Merge appointment data into the doctors array
        foreach ($rows as $row) {
            $doctorId = $row->doctor_id;
            if (!isset($doctors[$doctorId])) {
                $doctors[$doctorId] = [
                    'doctor_id'    => $doctorId,
                    'doctor_name'  => NameHelper::join($row->d_surname, $row->d_othername),
                    'total'        => 0,
                    'waiting'      => 0,
                    'in_treatment' => 0,
                    'completed'    => 0,
                    'patients'     => [],
                ];
            }

            $status = $this->resolveDisplayStatus($row->apt_status, $row->queue_status);
            $doctors[$doctorId]['total']++;

            if (in_array($status, ['waiting', 'called'])) {
                $doctors[$doctorId]['waiting']++;
            } elseif ($status === 'in_treatment') {
                $doctors[$doctorId]['in_treatment']++;
            } elseif ($status === 'completed') {
                $doctors[$doctorId]['completed']++;
            }

            $doctors[$doctorId]['patients'][] = [
                'appointment_id' => $row->appointment_id,
                'patient_id'     => $row->patient_id,
                'patient_name'   => NameHelper::join($row->p_surname, $row->p_othername),
                'service'        => $row->service_name ?? '',
                'start_time'     => $row->start_time ? date('H:i', strtotime($row->start_time)) : '',
                'status'         => $status,
                'check_in_time'  => $row->check_in_time,
            ];
        }

        return array_values($doctors);
    }

    /**
     * Get badge counts for the info tabs (followups, tomorrow, week missed, birthdays).
     */
    public function getTabCounts(int $branchId, ?string $date = null): array
    {
        $today = $date ?? date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
        $weekAgo = date('Y-m-d', strtotime($today . ' -7 days'));
        $monthDay = date('m-d', strtotime($today));

        return [
            'followups' => (int) DB::table('patient_followups')
                ->whereDate('scheduled_date', $today)
                ->whereNull('deleted_at')
                ->count(),
            'tomorrow' => (int) DB::table('appointments')
                ->where('start_date', $tomorrow)
                ->whereNull('deleted_at')
                ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
                ->count(),
            'week_missed' => (int) DB::table('appointments')
                ->where('start_date', '>=', $weekAgo)
                ->where('start_date', '<', $today)
                ->where('status', Appointment::STATUS_NO_SHOW)
                ->whereNull('deleted_at')
                ->count(),
            'birthdays' => (int) DB::table('patients')
                ->whereNull('deleted_at')
                ->whereRaw('DATE_FORMAT(date_of_birth, "%m-%d") = ?', [$monthDay])
                ->count(),
            'paid' => (int) DB::table('invoice_payments')
                ->where('payment_date', $today)
                ->whereNull('deleted_at')
                ->count(),
            'unpaid' => (int) DB::table('invoices')
                ->whereDate('invoice_date', $today)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereNull('deleted_at')
                ->count(),
            'lab_cases' => (int) DB::table('lab_cases')
                ->where(function ($q) use ($today) {
                    $q->where('expected_return_date', $today)
                      ->orWhere('actual_return_date', $today);
                })
                ->whereNull('deleted_at')
                ->count(),
        ];
    }

    /**
     * Get all active doctors for the filter dropdown.
     */
    public function getDoctors(): array
    {
        return DB::table('users')
            ->where('is_doctor', 1)
            ->whereNull('deleted_at')
            ->select('id', 'surname', 'othername')
            ->orderBy('surname')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => NameHelper::join($row->surname, $row->othername),
                ];
            })
            ->toArray();
    }

    /**
     * Mark an appointment as no-show.
     */
    public function markNoShow(int $appointmentId): bool
    {
        return (bool) DB::table('appointments')
            ->where('id', $appointmentId)
            ->whereNull('deleted_at')
            ->update(['status' => Appointment::STATUS_NO_SHOW]);
    }
}
