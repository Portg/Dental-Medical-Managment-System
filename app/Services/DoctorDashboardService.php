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
                'status' => 'Waiting',
            ])->count(),
            'new_patients' => Patient::where('created_at', Carbon::today())->count(),
            'monthly_appointments' => $this->buildMonthlyAppointmentsChart($doctorId),
            'monthly_appointments_classification' => $this->buildMonthlyClassificationChart($doctorId),
        ];
    }

    /**
     * Build monthly appointments line chart.
     */
    private function buildMonthlyAppointmentsChart(int $doctorId): MonthlyAppointmentsChart
    {
        $dailyAppointments = DB::table('appointments')
            ->select(DB::raw('count(id) as daily_appointments'), DB::raw('date(sort_by) as dates'))
            ->whereNull('deleted_at')
            ->where('doctor_id', $doctorId)
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
        $chart->dataset('Daily Appointments', 'line', $data)->options([
            'fill' => false,
        ]);

        return $chart;
    }

    /**
     * Build monthly appointments classification pie chart.
     */
    private function buildMonthlyClassificationChart(int $doctorId): MonthlyAppointmentsClassificationChart
    {
        $singleCount = DB::table('appointments')
            ->whereNull('deleted_at')
            ->where('visit_information', 'Single Treatment')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->where('doctor_id', $doctorId)
            ->count('id');

        $reviewCount = DB::table('appointments')
            ->whereNull('deleted_at')
            ->where('visit_information', 'Review Treatment')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->where('doctor_id', $doctorId)
            ->count('id');

        $chart = new MonthlyAppointmentsClassificationChart;
        $chart->labels(['Single Treatment', 'Review Treatment']);
        $chart->dataset('Daily Appointments', 'pie', [$singleCount, $reviewCount])
            ->options([
                'backgroundColor' => ['#3598DC', '#78CC66'],
            ]);

        return $chart;
    }
}
