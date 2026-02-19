<?php

namespace App\Services;

use App\Appointment;
use App\Charts\AppointmentStatusChart;
use App\Invoice;
use App\InvoicePayment;
use App\WaitingQueue;
use Illuminate\Support\Facades\DB;

class ReceptionistDashboardService
{
    /**
     * Get all dashboard data for the receptionist.
     */
    public function getDashboardData(): array
    {
        return [
            'today_appointments' => Appointment::today()->count(),
            'today_cash_amount' => InvoicePayment::where('payment_method', 'Cash')
                ->whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'pending_receivable_amount' => Invoice::whereIn('payment_status', [Invoice::PAYMENT_UNPAID, Invoice::PAYMENT_PARTIAL])
                ->sum('outstanding_amount'),
            'waiting_queue_count' => WaitingQueue::today()->count(),
            'appointmentStatusChart' => $this->buildAppointmentStatusChart(),
        ];
    }

    /**
     * Build today's appointment status distribution pie chart.
     */
    private function buildAppointmentStatusChart(): AppointmentStatusChart
    {
        $statusCounts = DB::table('appointments')
            ->select('status', DB::raw('count(id) as total'))
            ->whereNull('deleted_at')
            ->whereDate('start_date', date('Y-m-d'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        $colors = [
            Appointment::STATUS_WAITING => '#F1C40F',
            Appointment::STATUS_IN_PROGRESS => '#3498DB',
            Appointment::STATUS_COMPLETED => '#2ECC71',
            Appointment::STATUS_TREATMENT_COMPLETE => '#27AE60',
            Appointment::STATUS_NO_SHOW => '#E74C3C',
            Appointment::STATUS_CANCELLED => '#95A5A6',
            Appointment::STATUS_SCHEDULED => '#9B59B6',
            Appointment::STATUS_CHECKED_IN => '#1ABC9C',
        ];
        $bgColors = [];

        foreach ($statusCounts as $status => $count) {
            $labels[] = $status;
            $data[] = $count;
            $bgColors[] = $colors[$status] ?? '#BDC3C7';
        }

        $chart = new AppointmentStatusChart;
        $chart->labels($labels);
        $chart->dataset(__('dashboard.appointment_status_distribution'), 'pie', $data)
            ->options([
                'backgroundColor' => $bgColors,
            ]);

        return $chart;
    }
}
