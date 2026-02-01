<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\MedicalTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MedicalTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('medical_templates')
                ->leftJoin('users', 'users.id', 'medical_templates.created_by')
                ->whereNull('medical_templates.deleted_at')
                ->select(
                    'medical_templates.*',
                    'users.surname as creator_name'
                );

            // Filter by category
            if ($request->has('category') && $request->category) {
                $query->where('medical_templates.category', $request->category);
            }

            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->where('medical_templates.type', $request->type);
            }

            $data = $query->orderBy('medical_templates.usage_count', 'desc')->get();

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

        $content = $request->content;
        if (is_array($content)) {
            $content = json_encode($content);
        }

        $template = MedicalTemplate::create([
            'name' => $request->name,
            'code' => $request->code,
            'category' => $request->category,
            'type' => $request->type,
            'content' => $content,
            'department' => $request->department,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'created_by' => Auth::user()->id,
            '_who_added' => Auth::user()->id,
        ]);

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
        $template = MedicalTemplate::with('creator')->findOrFail($id);
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

        $content = $request->content;
        if (is_array($content)) {
            $content = json_encode($content);
        }

        $status = MedicalTemplate::where('id', $id)->update([
            'name' => $request->name,
            'code' => $request->code,
            'category' => $request->category,
            'type' => $request->type,
            'content' => $content,
            'department' => $request->department,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

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
        $status = MedicalTemplate::where('id', $id)->delete();

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
        $userId = Auth::user()->id;
        $type = $request->get('type', 'progress_note');
        $q = $request->get('q', '');

        $query = MedicalTemplate::active()
            ->availableToUser($userId)
            ->byType($type)
            ->select('id', 'name', 'code', 'category', 'content', 'description', 'usage_count');

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $templates = $query->orderBy('usage_count', 'desc')
            ->limit(20)
            ->get();

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
        $template = MedicalTemplate::findOrFail($id);
        $template->incrementUsage();

        return response()->json([
            'status' => true,
            'usage_count' => $template->usage_count
        ]);
    }
}
