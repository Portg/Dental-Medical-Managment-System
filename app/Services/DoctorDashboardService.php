<?php

namespace App\Services;

use App\Appointment;
use App\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Doctor\Charts\MonthlyAppointmentsChart;
use Modules\Doctor\Charts\MonthlyAppointmentsClassificationChart;

class DoctorDashboardService
{
    /**
     * Get all dashboard data for the doctor.
     */
    public function getDashboardData(): array
    {
        $doctorId = Auth::User()->id;

        return [
            'appointments' => Appointment::where([
                'doctor_id' => $doctorId,
                'sort_by' => Carbon::today(),
            ])->count(),
            'pending_appointments' => Appointment::where([
                'doctor_id' => $doctorId,
                'status' => Appointment::STATUS_WAITING,
            ])->count(),
            'new_patients' => Patient::whereDate('created_at', Carbon::today())
                ->whereHas('appointments', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->count(),
            'monthly_appointments' => $this->buildMonthlyAppointmentsChart($doctorId),
            'monthly_appointments_classification' => $this->buildMonthlyClassificationChart($doctorId),
        ];
    }

    /**
     * Build monthly appointments line chart (current month only).
     */
    private function buildMonthlyAppointmentsChart(int $doctorId): MonthlyAppointmentsChart
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $dailyAppointments = DB::table('appointments')
            ->select(DB::raw('count(id) as daily_appointments'), DB::raw('date(sort_by) as dates'))
            ->whereNull('deleted_at')
            ->where('doctor_id', $doctorId)
            ->whereRaw('MONTH(sort_by) = ? AND YEAR(sort_by) = ?', [$currentMonth, $currentYear])
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $labels = [];
        $data = [];
        foreach ($dailyAppointments as $item) {
            $labels[] = $item->dates;
            $data[] = $item->daily_appointments;
        }

        $chart = new MonthlyAppointmentsChart;
        $chart->labels($labels);
        $chart->dataset(__('dashboard.today_appointments'), 'line', $data)->options([
            'fill' => false,
        ]);

        return $chart;
    }

    /**
     * Build monthly appointments classification pie chart (current month only).
     */
    private function buildMonthlyClassificationChart(int $doctorId): MonthlyAppointmentsClassificationChart
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $singleCount = DB::table('appointments')
            ->whereNull('deleted_at')
            ->where('visit_information', Appointment::VISIT_SINGLE_TREATMENT)
            ->whereRaw('MONTH(created_at) = ? AND YEAR(created_at) = ?', [$currentMonth, $currentYear])
            ->where('doctor_id', $doctorId)
            ->count('id');

        $reviewCount = DB::table('appointments')
            ->whereNull('deleted_at')
            ->where('visit_information', Appointment::VISIT_REVIEW_TREATMENT)
            ->whereRaw('MONTH(created_at) = ? AND YEAR(created_at) = ?', [$currentMonth, $currentYear])
            ->where('doctor_id', $doctorId)
            ->count('id');

        $chart = new MonthlyAppointmentsClassificationChart;
        $chart->labels([__('dashboard.single_treatment'), __('dashboard.review_treatment')]);
        $chart->dataset(__('dashboard.appointment_classification'), 'pie', [$singleCount, $reviewCount])
            ->options([
                'backgroundColor' => ['#3598DC', '#78CC66'],
            ]);

        return $chart;
    }
}
