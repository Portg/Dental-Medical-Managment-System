<?php

namespace App\Services;

use App\Appointment;
use App\SatisfactionSurvey;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SatisfactionSurveyService
{
    /**
     * Get dashboard statistics for the index page.
     */
    public function getDashboardData(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $nps = SatisfactionSurvey::calculateNPS(null, $start, $end);

        $avgRatings = SatisfactionSurvey::getAverageRatings(null, $start, $end);

        $totalSurveys = SatisfactionSurvey::completed()
            ->whereBetween('survey_date', [$start, $end])
            ->count();

        $pendingSurveys = SatisfactionSurvey::where('status', SatisfactionSurvey::STATUS_PENDING)->count();

        $ratingDistribution = SatisfactionSurvey::completed()
            ->whereBetween('survey_date', [$start, $end])
            ->select('overall_rating', DB::raw('COUNT(*) as count'))
            ->groupBy('overall_rating')
            ->orderBy('overall_rating')
            ->get()
            ->keyBy('overall_rating');

        $monthlyTrend = $this->getMonthlyTrend(6);

        $doctorRankings = SatisfactionSurvey::completed()
            ->whereBetween('survey_date', [$start, $end])
            ->select('doctor_id', DB::raw('AVG(doctor_rating) as avg_rating'), DB::raw('COUNT(*) as count'))
            ->whereNotNull('doctor_id')
            ->groupBy('doctor_id')
            ->orderByDesc('avg_rating')
            ->with('doctor')
            ->limit(10)
            ->get();

        return compact(
            'nps', 'avgRatings', 'totalSurveys', 'pendingSurveys',
            'ratingDistribution', 'monthlyTrend', 'doctorRankings'
        ) + ['startDate' => $start, 'endDate' => $end];
    }

    /**
     * Get filtered survey query for DataTables.
     */
    public function getSurveyQuery(array $filters): Builder
    {
        $query = SatisfactionSurvey::with(['patient', 'doctor', 'appointment'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('survey_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query;
    }

    /**
     * Create a survey for an appointment.
     */
    public function createSurvey(int $appointmentId, string $channel): SatisfactionSurvey
    {
        $appointment = Appointment::findOrFail($appointmentId);

        return SatisfactionSurvey::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor,
            'branch_id' => Auth::user()->branch_id,
            'survey_channel' => $channel,
            'status' => SatisfactionSurvey::STATUS_PENDING,
        ]);
    }

    /**
     * Get survey detail with related data.
     */
    public function getSurveyDetail(int $id): SatisfactionSurvey
    {
        return SatisfactionSurvey::with(['patient', 'doctor', 'appointment', 'branch'])->findOrFail($id);
    }

    /**
     * Submit survey responses.
     */
    public function submitSurvey(int $id, array $data): bool
    {
        $survey = SatisfactionSurvey::findOrFail($id);

        return (bool) $survey->update([
            'overall_rating' => $data['overall_rating'],
            'service_rating' => $data['service_rating'] ?? null,
            'environment_rating' => $data['environment_rating'] ?? null,
            'wait_time_rating' => $data['wait_time_rating'] ?? null,
            'doctor_rating' => $data['doctor_rating'] ?? null,
            'would_recommend' => $data['would_recommend'] ?? null,
            'feedback' => $data['feedback'] ?? null,
            'suggestions' => $data['suggestions'] ?? null,
            'survey_date' => now(),
            'status' => SatisfactionSurvey::STATUS_COMPLETED,
        ]);
    }

    /**
     * Send surveys in batch for completed appointments on a given date.
     */
    public function sendBatch(string $date, string $channel): int
    {
        $appointments = Appointment::where('appointment_date', $date)
            ->where('status', 'completed')
            ->whereDoesntHave('satisfactionSurvey')
            ->get();

        $sentCount = 0;
        foreach ($appointments as $appointment) {
            SatisfactionSurvey::create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor,
                'branch_id' => Auth::user()->branch_id,
                'survey_channel' => $channel,
                'status' => SatisfactionSurvey::STATUS_PENDING,
            ]);
            $sentCount++;
        }

        return $sentCount;
    }

    /**
     * Get monthly trend data for the last N months.
     */
    private function getMonthlyTrend(int $months): array
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $avgRating = SatisfactionSurvey::completed()
                ->whereBetween('survey_date', [$monthStart, $monthEnd])
                ->avg('overall_rating');

            $nps = SatisfactionSurvey::calculateNPS(null, $monthStart, $monthEnd);

            $count = SatisfactionSurvey::completed()
                ->whereBetween('survey_date', [$monthStart, $monthEnd])
                ->count();

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'month_label' => __('datetime.months_short.' . ($monthStart->month - 1)),
                'avg_rating' => round($avgRating ?? 0, 1),
                'nps' => $nps ?? 0,
                'count' => $count,
            ];
        }

        return $trend;
    }
}
