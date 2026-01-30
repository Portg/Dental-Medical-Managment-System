<?php

namespace App\Http\Controllers;

use App\PatientTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class PatientTagController extends Controller
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
            $query = DB::table('patient_tags')
                ->leftJoin('users', 'users.id', 'patient_tags._who_added')
                ->whereNull('patient_tags.deleted_at')
                ->select(
                    'patient_tags.*',
                    'users.surname as added_by_name'
                );

            // Quick search filter
            if ($request->has('quick_search') && !empty($request->quick_search)) {
                $search = $request->quick_search;
                $query->where('patient_tags.name', 'like', '%' . $search . '%');
            }

            // Status filter (use is_numeric to handle '0' correctly)
            if ($request->has('status') && is_numeric($request->status)) {
                $query->where('patient_tags.is_active', $request->status);
            }

            $data = $query->orderBy('patient_tags.sort_order', 'asc')
                ->orderBy('patient_tags.name', 'asc')
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('color_badge', function ($row) {
                    return '<span class="label" style="background-color: ' . $row->color . ';">' . $row->name . '</span>';
                })
                ->addColumn('patients_count', function ($row) {
                    return DB::table('patient_tag_pivot')
                        ->where('tag_id', $row->id)
                        ->count();
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
        $query = PatientTag::active()->ordered();

        // Support search
        if ($request->has('q') && !empty($request->q)) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $tags = $query->select('id', 'name', 'color', 'icon')->get();

        // Format for Select2
        $results = $tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'text' => $tag->name,
                'color' => $tag->color,
                'icon' => $tag->icon
            ];
        });

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

        $tag = PatientTag::create([
            'name' => $request->name,
            'color' => $request->color,
            'icon' => $request->icon,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            '_who_added' => Auth::user()->id,
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
        $tag = PatientTag::findOrFail($id);
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

        $status = PatientTag::where('id', $id)->update([
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
        // Remove pivot entries first
        DB::table('patient_tag_pivot')->where('tag_id', $id)->delete();

        $status = PatientTag::where('id', $id)->delete();

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
