<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\Services\MedicalTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MedicalTemplateController extends Controller
{
    private MedicalTemplateService $medicalTemplateService;

    public function __construct(MedicalTemplateService $medicalTemplateService)
    {
        $this->medicalTemplateService = $medicalTemplateService;
        // 这三个方法必须从 manage-medical-services 中排除：两条 middleware 原先是叠加
        // 而非分流，导致它们要求同时持有两个权限，与下方注释声明的意图相反——医生持有
        // manage-medical-cases 却无 manage-medical-services，仍被第一条挡死，病历模板
        // 浮层因此取不到数据。
        $this->middleware('can:manage-medical-services')->except(['store', 'search', 'incrementUsage']);

        // search 与 incrementUsage 用 edit-patients 而非 manage-medical-cases：模板浮层
        // 绑定在 .template-enabled 输入框上（template_picker.js），该 class 只出现在诊断、
        // 治疗计划、病程记录三个书写页，而这些页面的准入正是 edit-patients（超管、管理员、
        // 医生、护士、前台）。用 manage-medical-cases 把关会漏掉前台——前台能进书写页，
        // 却在敲字时拿不到模板；选用模板后回写用量的 incrementUsage 同理。
        $this->middleware('can:edit-patients')->only(['search', 'incrementUsage']);

        // store（把当前病历存为个人模板）仍限 manage-medical-cases：沉淀模板内容与使用
        // 模板是两回事，保持原有的医生/护士边界。
        $this->middleware('can:manage-medical-cases')->only(['store']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->medicalTemplateService->getTemplateList([
                'search'   => $request->input('search.value', ''),
                'category' => $request->input('category'),
                'type'     => $request->input('type'),
            ]);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('category_label', function ($row) {
                    $labels = [
                        'system' => '<span class="label label-primary">' . __('templates.system') . '</span>',
                        'department' => '<span class="label label-info">' . __('templates.department') . '</span>',
                        'personal' => '<span class="label label-default">' . __('templates.personal') . '</span>',
                    ];
                    return $labels[$row->category] ?? $row->category;
                })
                ->addColumn('type_label', function ($row) {
                    $labels = [
                        'progress_note' => __('templates.progress_note'),
                        'diagnosis' => __('templates.diagnosis'),
                        'treatment_plan' => __('templates.treatment_plan'),
                        'chief_complaint' => __('templates.chief_complaint'),
                    ];
                    return $labels[$row->type] ?? $row->type;
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="text-primary">' . __('common.active') . '</span>';
                    }
                    return '<span class="text-danger">' . __('common.inactive') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return ActionColumnHelper::make($row->id)
                        ->primary('preview', __('templates.preview'), '#', 'previewTemplate')
                        ->add('edit', __('common.edit'), '#', 'editTemplate')
                        ->add('delete', __('common.delete'), '#', 'deleteTemplate')
                        ->render();
                })
                ->rawColumns(['category_label', 'status', 'action'])
                ->make(true);
        }

        return view('medical_templates.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'category' => 'required|in:system,department,personal',
            'type' => 'required|in:progress_note,diagnosis,treatment_plan,chief_complaint',
            'content' => 'required',
        ];

        // Code is optional for personal templates (auto-generated if empty)
        if ($request->filled('code')) {
            $rules['code'] = 'string|max:50|unique:medical_templates,code,NULL,id,deleted_at,NULL';
        }

        Validator::make($request->all(), $rules)->validate();

        $data = $request->only(['name', 'code', 'category', 'type', 'content', 'description']);

        // AG-022: Non-admin users can only create personal templates
        if (!Auth::user()->can('manage-medical-services')) {
            $data['category'] = 'personal';
        }

        $template = $this->medicalTemplateService->createTemplate($data, Auth::user()->id);

        if ($template) {
            return response()->json([
                'message' => __('messages.template_created_successfully'),
                'status' => true,
                'data' => $template
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $template = $this->medicalTemplateService->getTemplateDetail((int) $id);
        return response()->json([
            'status' => true,
            'data' => $template
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:medical_templates,code,' . $id . ',id,deleted_at,NULL',
            'category' => 'required|in:system,department,personal',
            'type' => 'required|in:progress_note,diagnosis,treatment_plan,chief_complaint',
            'content' => 'required',
        ])->validate();

        $status = $this->medicalTemplateService->updateTemplate((int) $id, $request->only(['name', 'code', 'category', 'type', 'content']));

        if ($status) {
            return response()->json([
                'message' => __('messages.template_updated_successfully'),
                'status' => true
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $status = $this->medicalTemplateService->deleteTemplate((int) $id);

        if ($status) {
            return response()->json([
                'message' => __('messages.template_deleted_successfully'),
                'status' => true
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }

    /**
     * Search templates for quick insertion.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $templates = $this->medicalTemplateService->searchTemplates(
            Auth::user()->id,
            $request->get('type') ?? 'progress_note',
            $request->get('q') ?? ''
        );

        return response()->json([
            'status' => true,
            'data' => $templates
        ]);
    }

    /**
     * Increment usage count for a template.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function incrementUsage($id)
    {
        $usageCount = $this->medicalTemplateService->incrementUsage((int) $id);

        return response()->json([
            'status' => true,
            'usage_count' => $usageCount
        ]);
    }
}
