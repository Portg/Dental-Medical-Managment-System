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
        return $this->firstOrCreateFor(Appointment::findOrFail($appointmentId), $channel);
    }

    /**
     * 为已取出的预约建问卷。批量路径已经有模型在手，走这里可以省掉一次 findOrFail。
     */
    private function firstOrCreateFor(Appointment $appointment, string $channel): SatisfactionSurvey
    {
        // 一次就诊一份问卷（关系与测试都这么声明）。firstOrCreate 配合
        // appointment_id 上的唯一索引，才能挡住「查不存在→创建」之间的并发窗口。
        return SatisfactionSurvey::firstOrCreate(['appointment_id' => $appointment->id], [
            'token'          => SatisfactionSurvey::generateToken(),
            'patient_id'     => $appointment->patient_id,
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

        // 先读后写挡不住并发：读到 pending 之后患者可能正好提交完成，此时按主键
        // 无条件 update 会把 completed 改回 pending 并发新链接 —— 等于把患者已经
        // 给出的评价重新开放给任何人覆盖。判定必须放进带 status 条件的更新里。
        $affected = SatisfactionSurvey::where('id', $survey->id)
            ->where('status', '<>', SatisfactionSurvey::STATUS_COMPLETED)
            ->update([
                'token'      => SatisfactionSurvey::generateToken(),
                'status'     => SatisfactionSurvey::STATUS_PENDING,
                'sent_at'    => now(),
                'expires_at' => now()->addDays(SatisfactionSurvey::DEFAULT_VALID_DAYS),
            ]);

        if ($affected === 0) {
            throw new \RuntimeException(__('satisfaction.already_completed'));
        }

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
     *
     * $token 是患者手上那条链接的 token。必须一路带到最终的原子更新里：
     * 「按 token 查出问卷」和「提交」之间隔着一整个填表过程，管理员完全可能在这中间
     * 重置链接。只按 id 判定的话，本该失效的旧链接照样能提交成功。
     */
    public function submitSurvey(int $id, array $data, ?string $token = null): bool
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

        // 上面的状态检查挡不住并发：两个请求可以同时读到 pending，后写的一份会
        // 覆盖先写的评价。真正的判定放在带条件的更新里，靠影响行数决定谁抢到；
        // 匿名标志也必须在同一次更新内落库，否则会出现「已完成但匿名标志还没写」的中间态。
        //
        // 条件里除了 status，还要带上 token 与有效期：
        //   - token：管理员可能在患者填表期间重置了链接，旧链接必须当场失效；
        //   - expires_at：上面的 isExpired() 读的是内存里的旧模型，同样有窗口。
        $query = SatisfactionSurvey::where('id', $survey->id)
            ->where('status', SatisfactionSurvey::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });

        if ($token !== null) {
            $query->where('token', $token);
        }

        $affected = $query
            ->update([
                'overall_rating' => $data['overall_rating'],
                'service_rating' => $data['service_rating'] ?? null,
                'environment_rating' => $data['environment_rating'] ?? null,
                'wait_time_rating' => $data['wait_time_rating'] ?? null,
                'doctor_rating' => $data['doctor_rating'] ?? null,
                'would_recommend' => $data['would_recommend'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'suggestions' => $data['suggestions'] ?? null,
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'survey_date' => now(),
                'status' => SatisfactionSurvey::STATUS_COMPLETED,
            ]);

        if ($affected === 0) {
            // 并发下输给了另一个请求、状态已被改成 expired，或链接已被管理员重置
            throw new \RuntimeException(__('satisfaction.already_completed'));
        }

        return true;
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

        // 上面的 whereDoesntHave 是「先查不存在」，与下面的写入之间有并发窗口：
        // 两个批量请求可以同时判定同一预约没有问卷。appointment_id 上有唯一索引，
        // 届时后写的一方会抛 QueryException，而循环外没有事务 —— 前几条已提交、
        // 后面整批中断。改走 createSurvey() 的 firstOrCreate，让唯一索引成为
        // 幂等的依据而不是报错的来源。
        $created = [];
        foreach ($appointments as $appointment) {
            $created[] = $this->firstOrCreateFor($appointment, $channel);
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
