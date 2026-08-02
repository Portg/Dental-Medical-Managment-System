<?php

namespace App\Http\Controllers;

use App\Services\SatisfactionSurveyService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SatisfactionSurveyController extends Controller
{
    private SatisfactionSurveyService $service;

    public function __construct(SatisfactionSurveyService $service)
    {
        $this->service = $service;
        // 满意度调查属于患者关怀日常运营（前台为主力），不是系统设置。
        // 但「看」和「改」必须分开：view-patients 授予了全部业务角色，
        // 挂在整个控制器上等于让任何能看患者的人都能批量生成问卷、重置患者填写链接。
        $this->middleware('can:view-surveys');
        $this->middleware('can:manage-surveys')->only(['create', 'store', 'sendBatch', 'regenerateLink']);
    }

    /**
     * Display the survey dashboard.
     */
    public function index(Request $request)
    {
        $data = $this->service->getDashboardData($request->start_date, $request->end_date);

        return view('satisfaction_surveys.index', $data);
    }

    /**
     * Get survey data for DataTable.
     */
    public function getData(Request $request)
    {
        $query = $this->service->getSurveyQuery([
            'search'     => $request->input('search.value', ''),
            'status'     => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'doctor_id'  => $request->input('doctor_id'),
        ]);

        return DataTables::of($query)
            ->addColumn('patient_name', function($row) {
                // 匿名判断必须在取姓名之前。原写法是 $row->patient->name ?? (匿名?...)，
                // 既把匿名判断放在了后面，又读了 patients 表根本没有的 name 列
                // （恒为 null），结果非匿名问卷的姓名也从来显示不出来。
                if ($row->is_anonymous) {
                    return __('satisfaction.anonymous');
                }

                return optional($row->patient)->full_name ?: '-';
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
     * Show the create survey form.
     */
    public function create()
    {
        return view('satisfaction_surveys.create');
    }

    /**
     * Send a survey for an appointment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'channel' => 'required|in:sms,wechat,app,instore'
        ]);

        $survey = $this->service->createSurvey((int) $request->appointment_id, $request->channel);

        return response()->json([
            'status' => 'success',
            'message' => __('satisfaction.survey_sent'),
            'data' => $survey
        ]);
    }

    /**
     * Show survey detail.
     */
    public function show($id)
    {
        $survey = $this->service->getSurveyDetail((int) $id);

        return view('satisfaction_surveys.show', compact('survey'));
    }

    /**
     * 重新生成患者填写链接（原链接立即失效）。
     *
     * 短信通道尚未接入，因此「重发」在这里的含义是产出一条新的可复制链接，
     * 由前台通过微信等渠道人工发给患者，或现场用平板打开。
     */
    public function regenerateLink($id)
    {
        try {
            $survey = $this->service->regenerateToken((int) $id);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'status'     => 1,
            'message'    => __('satisfaction.link_regenerated'),
            'fill_url'   => $survey->fill_url,
            'expires_at' => optional($survey->expires_at)->format('Y-m-d H:i'),
        ]);
    }

    // 后台按数字 ID 提交评分的接口已移除：患者填写一律走
    // PublicSurveyController 的 token 链接，前端从未调用过这个入口，
    // 留着只是让任何有权限进本控制器的员工都能替患者伪造评价。

    /**
     * Send surveys in batch.
     */
    public function sendBatch(Request $request)
    {
        $request->validate([
            'date'    => 'required|date',
            'channel' => 'required|in:sms,wechat,instore'
        ]);

        $surveys = $this->service->sendBatch($request->date, $request->channel);

        // 短信通道未接入，这里不谎报「已发送」——返回生成的链接清单，
        // 由前台自行分发（微信转发 / 现场扫码），做到界面所述即实际所做。
        $links = collect($surveys)->map(function ($survey) {
            return [
                'id'           => $survey->id,
                'patient_name' => optional($survey->patient)->full_name,
                'fill_url'     => $survey->fill_url,
            ];
        })->values();

        return response()->json([
            'status'  => 1,
            'count'   => $links->count(),
            'links'   => $links,
            'message' => __('satisfaction.batch_generated', ['count' => $links->count()]),
        ]);
    }
}
