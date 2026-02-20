<?php

namespace App\Http\Controllers;

use App\Services\PatientTagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class PatientTagController extends Controller
{
    private PatientTagService $service;

    public function __construct(PatientTagService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-patient-settings');
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
            $data = $this->service->getTagList($request->only(['quick_search', 'status']));

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('color_badge', function ($row) {
                    return '<span class="label" style="background-color: ' . e($row->color) . ';">' . e($row->name) . '</span>';
                })
                ->addColumn('patients_count', function ($row) {
                    return (int) $row->patients_count;
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="text-primary">' . __('common.active') . '</span>';
                    }
                    return '<span class="text-danger">' . __('common.inactive') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                            ' . __('common.action') . '
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onclick="editTag(' . $row->id . ')">' . __('common.edit') . '</a>
                            </li>
                            <li>
                                <a href="#" onclick="deleteTag(' . $row->id . ')">' . __('common.delete') . '</a>
                            </li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['color_badge', 'status', 'action'])
                ->make(true);
        }

        return view('patient_tags.index');
    }

    /**
     * Get all active tags for select dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $results = $this->service->getActiveTagsForSelect($request->q);

        return response()->json($results);
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
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
        ])->validate();

        $tag = $this->service->createTag([
            'name' => $request->name,
            'color' => $request->color,
            'icon' => $request->icon,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        if ($tag) {
            return response()->json([
                'message' => __('messages.tag_created_successfully'),
                'status' => true,
                'data' => $tag
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
        $tag = $this->service->getTag((int) $id);
        return response()->json([
            'status' => true,
            'data' => $tag
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
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
        ])->validate();

        $status = $this->service->updateTag((int) $id, [
            'name' => $request->name,
            'color' => $request->color,
            'icon' => $request->icon,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        if ($status) {
            return response()->json([
                'message' => __('messages.tag_updated_successfully'),
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
        $status = $this->service->deleteTag((int) $id);

        if ($status) {
            return response()->json([
                'message' => __('messages.tag_deleted_successfully'),
                'status' => true
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }
}
