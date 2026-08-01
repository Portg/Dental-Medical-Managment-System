<?php

namespace App\Services;

use App\Appointment;
use App\SatisfactionSurvey;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
            'token'          => SatisfactionSurvey::generateToken(),
            'patient_id'     => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            // 注意：doctor 是 belongsTo 关联，不是字段；预约表上的列名为 doctor_id。
            // 原先写成 $appointment->doctor 会把 User 对象塞进 doctor_id。
            'doctor_id'      => $appointment->doctor_id,
            'branch_id'      => $appointment->branch_id ?? optional(Auth::user())->branch_id,
            'survey_channel' => $channel,
            'status'         => SatisfactionSurvey::STATUS_PENDING,
            'sent_at'        => now(),
            'expires_at'     => now()->addDays(SatisfactionSurvey::DEFAULT_VALID_DAYS),
        ]);
    }

    /**
     * 重新生成填写链接（原链接立即失效）。
     *
     * 用于「患者说没收到 / 链接过期」的场景：换新 token 并顺延有效期，
     * 已填写完成的问卷不允许重开，避免覆盖已有评价。
     */
    public function regenerateToken(int $id): SatisfactionSurvey
    {
        $survey = SatisfactionSurvey::findOrFail($id);

        if ($survey->status === SatisfactionSurvey::STATUS_COMPLETED) {
            throw new \RuntimeException(__('satisfaction.already_completed'));
        }

        $survey->update([
            'token'      => SatisfactionSurvey::generateToken(),
            'status'     => SatisfactionSurvey::STATUS_PENDING,
            'sent_at'    => now(),
            'expires_at' => now()->addDays(SatisfactionSurvey::DEFAULT_VALID_DAYS),
        ]);

        return $survey->refresh();
    }

    /**
     * 用公开 token 取出可填写的问卷。
     *
     * 返回 null 的三种情况：token 不存在、已填写过、链接已过期——
     * 对外统一不区分，避免 token 探测。过期的顺手置为 expired。
     */
    public function findFillableByToken(string $token): ?SatisfactionSurvey
    {
        $survey = SatisfactionSurvey::with(['patient', 'doctor', 'branch'])
            ->where('token', $token)
            ->first();

        if (!$survey) {
            return null;
        }

        if ($survey->isExpired() && $survey->status === SatisfactionSurvey::STATUS_PENDING) {
            $survey->update(['status' => SatisfactionSurvey::STATUS_EXPIRED]);
            return null;
        }

        return $survey->canBeFilled() ? $survey : null;
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

        // 已完成的问卷不允许再次提交——公开链接一旦泄露，
        // 否则任何人都能覆盖患者已给出的评价。
        if ($survey->status === SatisfactionSurvey::STATUS_COMPLETED) {
            throw new \RuntimeException(__('satisfaction.already_completed'));
        }

        if ($survey->isExpired()) {
            $survey->update(['status' => SatisfactionSurvey::STATUS_EXPIRED]);
            throw new \RuntimeException(__('satisfaction.link_expired'));
        }

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
    public function sendBatch(string $date, string $channel): array
    {
        // 三处字段名此前都是错的，任一都会让本方法直接抛异常：
        //   appointment_date → 预约表实际列名是 start_date
        //   status 'completed' → 用模型常量，避免字面量与枚举漂移
        //   $appointment->doctor → doctor 是关联，列名是 doctor_id
        $appointments = Appointment::whereDate('start_date', $date)
            ->where('status', Appointment::STATUS_COMPLETED)
            ->whereNull('deleted_at')
            ->whereDoesntHave('satisfactionSurvey')
            ->get();

        $created = [];
        foreach ($appointments as $appointment) {
            $created[] = SatisfactionSurvey::create([
                'token'          => SatisfactionSurvey::generateToken(),
                'patient_id'     => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'doctor_id'      => $appointment->doctor_id,
                'branch_id'      => $appointment->branch_id ?? optional(Auth::user())->branch_id,
                'survey_channel' => $channel,
                'status'         => SatisfactionSurvey::STATUS_PENDING,
                'sent_at'        => now(),
                'expires_at'     => now()->addDays(SatisfactionSurvey::DEFAULT_VALID_DAYS),
            ]);
        }

        return $created;
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
