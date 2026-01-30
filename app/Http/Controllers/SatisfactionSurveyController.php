<?php

namespace App\Http\Controllers;

use App\SatisfactionSurvey;
use App\Patient;
use App\Appointment;
use App\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class SatisfactionSurveyController extends Controller
{
    /**
     * 满意度调查列表
     */
    public function index(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        // NPS 分数
        $nps = SatisfactionSurvey::calculateNPS(null, $startDate, $endDate);

        // 平均评分
        $avgRatings = SatisfactionSurvey::getAverageRatings(null, $startDate, $endDate);

        // 调查统计
        $totalSurveys = SatisfactionSurvey::completed()
            ->whereBetween('survey_date', [$startDate, $endDate])
            ->count();

        $pendingSurveys = SatisfactionSurvey::where('status', SatisfactionSurvey::STATUS_PENDING)->count();

        // 评分分布
        $ratingDistribution = SatisfactionSurvey::completed()
            ->whereBetween('survey_date', [$startDate, $endDate])
            ->select('overall_rating', DB::raw('COUNT(*) as count'))
            ->groupBy('overall_rating')
            ->orderBy('overall_rating')
            ->get()
            ->keyBy('overall_rating');

        // 月度趋势
        $monthlyTrend = $this->getMonthlyTrend(6);

        // 医生评分排名
        $doctorRankings = SatisfactionSurvey::completed()
            ->whereBetween('survey_date', [$startDate, $endDate])
            ->select('doctor_id', DB::raw('AVG(doctor_rating) as avg_rating'), DB::raw('COUNT(*) as count'))
            ->whereNotNull('doctor_id')
            ->groupBy('doctor_id')
            ->orderByDesc('avg_rating')
            ->with('doctor')
            ->limit(10)
            ->get();

        return view('satisfaction_surveys.index', compact(
            'nps',
            'avgRatings',
            'totalSurveys',
            'pendingSurveys',
            'ratingDistribution',
            'monthlyTrend',
            'doctorRankings',
            'startDate',
            'endDate'
        ));
    }

    /**
     * 获取调查数据（DataTable）
     */
    public function getData(Request $request)
    {
        $query = SatisfactionSurvey::with(['patient', 'doctor', 'appointment'])
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('survey_date', [$request->start_date, $request->end_date]);
        }

        return DataTables::of($query)
            ->addColumn('patient_name', function($row) {
                return $row->patient->name ?? ($row->is_anonymous ? __('satisfaction.anonymous') : '-');
            })
            ->addColumn('doctor_name', function($row) {
                return $row->doctor->surname ?? '-';
            })
            ->addColumn('survey_date_formatted', function($row) {
                return $row->survey_date ? $row->survey_date->format('Y-m-d') : '-';
            })
            ->addColumn('ratings_display', function($row) {
                $stars = str_repeat('★', $row->overall_rating) . str_repeat('☆', 5 - $row->overall_rating);
                return '<span class="rating-stars">' . $stars . '</span>';
            })
            ->addColumn('status_badge', function($row) {
                $badges = [
                    'pending' => 'warning',
                    'completed' => 'success',
                    'expired' => 'default',
                ];
                $badge = $badges[$row->status] ?? 'default';
                return '<span class="label label-' . $badge . '">' . __('satisfaction.status.' . $row->status) . '</span>';
            })
            ->addColumn('action', function($row) {
                return '<a href="' . url('satisfaction-surveys/' . $row->id) . '" class="btn btn-xs btn-info">
                    <i class="icon-eye"></i> ' . __('common.view') . '
                </a>';
            })
            ->rawColumns(['ratings_display', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * 创建调查问卷（发送给患者）
     */
    public function create()
    {
        return view('satisfaction_surveys.create');
    }

    /**
     * 发送调查问卷
     */
    public function store(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'channel' => 'required|in:sms,wechat,app,instore'
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);

        $survey = SatisfactionSurvey::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor,
            'branch_id' => Auth::user()->branch_id,
            'survey_channel' => $request->channel,
            'status' => SatisfactionSurvey::STATUS_PENDING,
        ]);

        // TODO: Send survey via SMS/WeChat

        return response()->json([
            'status' => 'success',
            'message' => __('satisfaction.survey_sent'),
            'data' => $survey
        ]);
    }

    /**
     * 查看调查详情
     */
    public function show($id)
    {
        $survey = SatisfactionSurvey::with(['patient', 'doctor', 'appointment', 'branch'])->findOrFail($id);

        return view('satisfaction_surveys.show', compact('survey'));
    }

    /**
     * 患者填写调查问卷（公开链接）
     */
    public function fill($token)
    {
        // TODO: Implement token-based survey access
        return view('satisfaction_surveys.fill');
    }

    /**
     * 提交调查问卷
     */
    public function submit(Request $request, $id)
    {
        $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'service_rating' => 'nullable|integer|min:1|max:5',
            'environment_rating' => 'nullable|integer|min:1|max:5',
            'wait_time_rating' => 'nullable|integer|min:1|max:5',
            'doctor_rating' => 'nullable|integer|min:1|max:5',
            'would_recommend' => 'nullable|integer|min:0|max:10',
            'feedback' => 'nullable|string|max:1000',
            'suggestions' => 'nullable|string|max:1000',
        ]);

        $survey = SatisfactionSurvey::findOrFail($id);

        $survey->update([
            'overall_rating' => $request->overall_rating,
            'service_rating' => $request->service_rating,
            'environment_rating' => $request->environment_rating,
            'wait_time_rating' => $request->wait_time_rating,
            'doctor_rating' => $request->doctor_rating,
            'would_recommend' => $request->would_recommend,
            'feedback' => $request->feedback,
            'suggestions' => $request->suggestions,
            'survey_date' => now(),
            'status' => SatisfactionSurvey::STATUS_COMPLETED,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('satisfaction.thank_you')
        ]);
    }

    /**
     * 获取月度趋势
     */
    private function getMonthlyTrend($months)
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
                'month_label' => $this->localizedMonth($monthStart->month),
                'avg_rating' => round($avgRating ?? 0, 1),
                'nps' => $nps ?? 0,
                'count' => $count,
            ];
        }

        return $trend;
    }

    /**
     * 获取本地化月份名称
     */
    private function localizedMonth($month)
    {
        return __('datetime.months_short.' . ($month - 1));
    }

    /**
     * 批量发送调查
     */
    public function sendBatch(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'channel' => 'required|in:sms,wechat'
        ]);

        // 获取指定日期完成的预约
        $appointments = Appointment::where('appointment_date', $request->date)
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
                'survey_channel' => $request->channel,
                'status' => SatisfactionSurvey::STATUS_PENDING,
            ]);
            $sentCount++;
        }

        return response()->json([
            'status' => 'success',
            'message' => __('satisfaction.batch_sent', ['count' => $sentCount])
        ]);
    }
}
