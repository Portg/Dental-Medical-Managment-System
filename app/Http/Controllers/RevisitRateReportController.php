<?php

namespace App\Http\Controllers;

use App\Patient;
use App\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevisitRateReportController extends Controller
{
    /**
     * 复诊率统计报表
     */
    public function index(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        // 本期就诊患者数（去重）
        $currentPeriodPatients = Appointment::whereBetween('start_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'checked_in', 'in_progress'])
            ->distinct('patient_id')
            ->count('patient_id');

        // 本期初诊患者数（首次就诊）
        $firstVisitPatients = $this->getFirstVisitPatients($startDate, $endDate);

        // 本期复诊患者数
        $revisitPatients = $currentPeriodPatients - $firstVisitPatients;

        // 复诊率
        $revisitRate = $currentPeriodPatients > 0
            ? round(($revisitPatients / $currentPeriodPatients) * 100, 1)
            : 0;

        // 按月份统计复诊趋势（过去6个月）
        $monthlyTrend = $this->getMonthlyRevisitTrend(6);

        // 按医生统计复诊率
        $doctorStats = $this->getDoctorRevisitStats($startDate, $endDate);

        // 复诊间隔分析（上次就诊到本次就诊的天数分布）
        $intervalDistribution = $this->getRevisitIntervalDistribution($startDate, $endDate);

        // 流失患者分析（超过90天未复诊）
        $lostPatients = $this->getLostPatients(90);

        return view('reports.revisit_rate_report', compact(
            'currentPeriodPatients',
            'firstVisitPatients',
            'revisitPatients',
            'revisitRate',
            'monthlyTrend',
            'doctorStats',
            'intervalDistribution',
            'lostPatients',
            'startDate',
            'endDate'
        ));
    }

    /**
     * 获取首诊患者数
     */
    private function getFirstVisitPatients($startDate, $endDate)
    {
        // 患者在指定期间内首次就诊
        return Patient::whereHas('appointments', function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->whereIn('status', ['completed', 'checked_in', 'in_progress']);
            })
            ->whereDoesntHave('appointments', function($query) use ($startDate) {
                $query->where('start_date', '<', $startDate)
                    ->whereIn('status', ['completed', 'checked_in', 'in_progress']);
            })
            ->count();
    }

    /**
     * 获取月度复诊趋势
     */
    private function getMonthlyRevisitTrend($months)
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $totalPatients = Appointment::whereBetween('start_date', [$monthStart, $monthEnd])
                ->whereIn('status', ['completed', 'checked_in', 'in_progress'])
                ->distinct('patient_id')
                ->count('patient_id');

            $firstVisitPatients = $this->getFirstVisitPatients($monthStart, $monthEnd);
            $revisitPatients = $totalPatients - $firstVisitPatients;
            $revisitRate = $totalPatients > 0 ? round(($revisitPatients / $totalPatients) * 100, 1) : 0;

            // Generate localized month label
            $monthLabel = $monthStart->format('Y-m');
            if (app()->getLocale() == 'zh-CN') {
                $monthLabel = $monthStart->format('Y') . '年' . $monthStart->format('n') . '月';
            } else {
                $monthLabel = $monthStart->format('M Y');
            }

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'month_label' => $monthLabel,
                'total_patients' => $totalPatients,
                'first_visit' => $firstVisitPatients,
                'revisit' => $revisitPatients,
                'revisit_rate' => $revisitRate,
            ];
        }

        return $trend;
    }

    /**
     * 按医生统计复诊率
     */
    private function getDoctorRevisitStats($startDate, $endDate)
    {
        return DB::table('appointments as a')
            ->join('users as u', 'a.doctor_id', '=', 'u.id')
            ->select(
                'u.id as doctor_id',
                'u.surname as doctor_name',
                DB::raw('COUNT(DISTINCT a.patient_id) as total_patients'),
                DB::raw('COUNT(a.id) as total_appointments')
            )
            ->whereBetween('a.start_date', [$startDate, $endDate])
            ->whereIn('a.status', ['completed', 'checked_in', 'in_progress'])
            ->whereNull('a.deleted_at')
            ->groupBy('u.id', 'u.surname')
            ->orderByDesc('total_patients')
            ->limit(10)
            ->get()
            ->map(function($row) use ($startDate, $endDate) {
                // 计算该医生的复诊率
                $avgVisits = $row->total_patients > 0
                    ? round($row->total_appointments / $row->total_patients, 2)
                    : 0;

                $row->avg_visits_per_patient = $avgVisits;
                return $row;
            });
    }

    /**
     * 复诊间隔分布
     */
    private function getRevisitIntervalDistribution($startDate, $endDate)
    {
        // 简化的间隔分布统计
        return [
            ['label' => __('report.interval_within_7_days'), 'range' => '0-7', 'count' => $this->countRevisitsByInterval(0, 7, $startDate, $endDate)],
            ['label' => __('report.interval_8_14_days'), 'range' => '8-14', 'count' => $this->countRevisitsByInterval(8, 14, $startDate, $endDate)],
            ['label' => __('report.interval_15_30_days'), 'range' => '15-30', 'count' => $this->countRevisitsByInterval(15, 30, $startDate, $endDate)],
            ['label' => __('report.interval_31_60_days'), 'range' => '31-60', 'count' => $this->countRevisitsByInterval(31, 60, $startDate, $endDate)],
            ['label' => __('report.interval_61_90_days'), 'range' => '61-90', 'count' => $this->countRevisitsByInterval(61, 90, $startDate, $endDate)],
            ['label' => __('report.interval_over_90_days'), 'range' => '90+', 'count' => $this->countRevisitsByInterval(91, 365, $startDate, $endDate)],
        ];
    }

    /**
     * 统计特定间隔的复诊数量
     */
    private function countRevisitsByInterval($minDays, $maxDays, $startDate, $endDate)
    {
        // 简化实现：统计在指定期间内，距上次就诊天数在范围内的预约数
        return 0; // 实际项目中需要实现
    }

    /**
     * 获取流失患者列表
     */
    private function getLostPatients($days)
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return Patient::select('patients.*')
            ->join(DB::raw('(SELECT patient_id, MAX(start_date) as last_visit FROM appointments WHERE status IN ("completed", "checked_in", "in_progress") AND deleted_at IS NULL GROUP BY patient_id) as last_appointments'),
                'patients.id', '=', 'last_appointments.patient_id')
            ->where('last_appointments.last_visit', '<', $cutoffDate)
            ->orderBy('last_appointments.last_visit', 'asc')
            ->limit(20)
            ->get()
            ->map(function($patient) {
                $lastAppointment = Appointment::where('patient_id', $patient->id)
                    ->whereIn('status', ['completed', 'checked_in', 'in_progress'])
                    ->orderBy('start_date', 'desc')
                    ->first();

                $patient->last_visit_date = $lastAppointment ? $lastAppointment->start_date : null;
                $patient->days_since_visit = $lastAppointment
                    ? Carbon::parse($lastAppointment->start_date)->diffInDays(now())
                    : null;

                return $patient;
            });
    }

    /**
     * 导出报表
     */
    public function export(Request $request)
    {
        // TODO: Implement Excel export
    }
}
