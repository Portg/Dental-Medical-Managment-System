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
        $this->middleware('can:manage-settings');
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

    /**
     * Submit survey responses.
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

        $this->service->submitSurvey((int) $id, $request->only([
            'overall_rating', 'service_rating', 'environment_rating', 'wait_time_rating',
            'doctor_rating', 'would_recommend', 'feedback', 'suggestions',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => __('satisfaction.thank_you')
        ]);
    }

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
