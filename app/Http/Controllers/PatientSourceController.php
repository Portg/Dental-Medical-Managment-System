<?php

namespace App\Http\Controllers;

use App\PatientSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class PatientSourceController extends Controller
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
            $query = DB::table('patient_sources')
                ->leftJoin('users', 'users.id', 'patient_sources._who_added')
                ->whereNull('patient_sources.deleted_at')
                ->select(
                    'patient_sources.*',
                    'users.surname as added_by_name'
                );

            // Quick search filter
            if ($request->has('quick_search') && !empty($request->quick_search)) {
                $search = $request->quick_search;
                $query->where(function ($q) use ($search) {
                    $q->where('patient_sources.name', 'like', '%' . $search . '%')
                      ->orWhere('patient_sources.code', 'like', '%' . $search . '%');
                });
            }

            // Status filter (use is_numeric to handle '0' correctly)
            if ($request->has('status') && is_numeric($request->status)) {
                $query->where('patient_sources.is_active', $request->status);
            }

            $data = $query->orderBy('patient_sources.name', 'asc')->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patients_count', function ($row) {
                    return DB::table('patients')
                        ->where('source_id', $row->id)
                        ->whereNull('deleted_at')
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
                                <a href="#" onclick="editSource(' . $row->id . ')">' . __('common.edit') . '</a>
                            </li>
                            <li>
                                <a href="#" onclick="deleteSource(' . $row->id . ')">' . __('common.delete') . '</a>
                            </li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('patient_sources.index');
    }

    /**
     * Get all active sources for select dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $query = PatientSource::active()->orderBy('name', 'asc');

        // Support search
        if ($request->has('q') && !empty($request->q)) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $sources = $query->select('id', 'name', 'code')->get();

        // Format for Select2
        $results = $sources->map(function ($source) {
            return [
                'id' => $source->id,
                'text' => $source->name
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
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:patient_sources,code',
        ])->validate();

        $source = PatientSource::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            '_who_added' => Auth::user()->id,
        ]);

        if ($source) {
            return response()->json([
                'message' => __('messages.source_created_successfully'),
                'status' => true,
                'data' => $source
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
        $source = PatientSource::findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $source
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
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:patient_sources,code,' . $id,
        ])->validate();

        $status = PatientSource::where('id', $id)->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        if ($status) {
            return response()->json([
                'message' => __('messages.source_updated_successfully'),
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
        // Check if any patients are using this source
        $patientsCount = DB::table('patients')
            ->where('source_id', $id)
            ->whereNull('deleted_at')
            ->count();

        if ($patientsCount > 0) {
            return response()->json([
                'message' => __('messages.source_in_use'),
                'status' => false
            ]);
        }

        $status = PatientSource::where('id', $id)->delete();

        if ($status) {
            return response()->json([
                'message' => __('messages.source_deleted_successfully'),
                'status' => true
            ]);
        }

        return response()->json([
            'message' => __('messages.error_occurred'),
            'status' => false
        ]);
    }
}
