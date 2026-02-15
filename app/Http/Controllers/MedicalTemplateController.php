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
            $data = $this->medicalTemplateService->getTemplateList($request->all());

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
        Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:medical_templates,code',
            'category' => 'required|in:system,department,personal',
            'type' => 'required|in:progress_note,diagnosis,treatment_plan,chief_complaint',
            'content' => 'required',
        ])->validate();

        $template = $this->medicalTemplateService->createTemplate($request->all(), Auth::user()->id);

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
        $template = $this->medicalTemplateService->getTemplateDetail($id);
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
            'code' => 'required|string|max:50|unique:medical_templates,code,' . $id,
            'category' => 'required|in:system,department,personal',
            'type' => 'required|in:progress_note,diagnosis,treatment_plan,chief_complaint',
            'content' => 'required',
        ])->validate();

        $status = $this->medicalTemplateService->updateTemplate($id, $request->all());

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
        $status = $this->medicalTemplateService->deleteTemplate($id);

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
            $request->get('type', 'progress_note'),
            $request->get('q', '')
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
        $usageCount = $this->medicalTemplateService->incrementUsage($id);

        return response()->json([
            'status' => true,
            'usage_count' => $usageCount
        ]);
    }
}
