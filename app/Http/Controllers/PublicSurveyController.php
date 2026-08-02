<?php

namespace App\Http\Controllers;

use App\SatisfactionSurvey;
use App\Services\SatisfactionSurveyService;
use Illuminate\Http\Request;

/**
 * 患者侧满意度问卷（公开访问，无需登录）。
 *
 * 单独成一个控制器而不是挂在 SatisfactionSurveyController 上：
 * 后者的构造函数统一套了 can:manage-settings，任何面向患者的方法
 * 放进去都得逐个 except，容易在后续新增方法时漏掉而误暴露后台能力。
 *
 * 访问凭证是 satisfaction_surveys.token（32 位十六进制，密码学随机）。
 * token 不存在 / 已填写 / 已过期，一律返回同一个「链接无效」页面，
 * 不区分原因，避免被用来探测 token 是否存在。
 */
class PublicSurveyController extends Controller
{
    private SatisfactionSurveyService $service;

    public function __construct(SatisfactionSurveyService $service)
    {
        $this->service = $service;
    }

    /**
     * 展示填写表单。
     */
    public function fill(string $token)
    {
        $survey = $this->service->findFillableByToken($token);

        if (!$survey) {
            return response()->view('satisfaction_surveys.invalid', [], 404);
        }

        return view('satisfaction_surveys.fill', compact('survey'));
    }

    /**
     * 患者提交问卷。
     */
    public function submit(Request $request, string $token)
    {
        $survey = $this->service->findFillableByToken($token);

        if (!$survey) {
            return response()->json([
                'status'  => 0,
                'message' => __('satisfaction.link_invalid'),
            ], 404);
        }

        $validator = \Validator::make($request->all(), [
            'overall_rating'     => 'required|integer|min:1|max:5',
            'service_rating'     => 'nullable|integer|min:1|max:5',
            'environment_rating' => 'nullable|integer|min:1|max:5',
            'wait_time_rating'   => 'nullable|integer|min:1|max:5',
            'doctor_rating'      => 'nullable|integer|min:1|max:5',
            'would_recommend'    => 'nullable|integer|min:0|max:10',
            'feedback'           => 'nullable|string|max:1000',
            'suggestions'        => 'nullable|string|max:1000',
            'is_anonymous'       => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            // is_anonymous 必须随评价一起提交：单独再 update 一次的话，
            // 两次写之间问卷已经是 completed 状态，此刻若被读取就会带出患者身份。
            // token 一并传下去：查出问卷到提交之间隔着整个填表过程，
            // 管理员可能已经重置过链接，旧链接必须在最终写入时被挡掉。
            $this->service->submitSurvey($survey->id, $request->only([
                'overall_rating', 'service_rating', 'environment_rating', 'wait_time_rating',
                'doctor_rating', 'would_recommend', 'feedback', 'suggestions',
            ]) + ['is_anonymous' => $request->boolean('is_anonymous')], $token);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'status'  => 1,
            'message' => __('satisfaction.thank_you'),
        ]);
    }
}
