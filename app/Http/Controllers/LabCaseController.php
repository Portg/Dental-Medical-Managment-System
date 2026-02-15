<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\LabCase;
use App\Services\LabCaseService;
use App\Services\LabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PDF;
use Yajra\DataTables\DataTables;

class LabCaseController extends Controller
{
    public function __construct(
        private LabCaseService $labCaseService,
        private LabService $labService,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only(['status', 'lab_id', 'doctor_id']);
            $filters['search'] = $request->input('search.value');
            $data = $this->labCaseService->getLabCaseList($filters);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('lab_case_no', function ($row) {
                    return '<a href="' . url('lab-cases/' . $row->id) . '">' . $row->lab_case_no . '</a>';
                })
                ->addColumn('patient_name', function ($row) {
                    return $row->patient_name ?? '-';
                })
                ->addColumn('doctor_name', function ($row) {
                    return $row->doctor_name ?? '-';
                })
                ->addColumn('lab_name', function ($row) {
                    return $row->lab_name ?? '-';
                })
                ->addColumn('prosthesis_type_label', function ($row) {
                    return __('lab_cases.type_' . $row->prosthesis_type);
                })
                ->addColumn('status_label', function ($row) {
                    $badges = [
                        'pending'       => 'default',
                        'sent'          => 'info',
                        'in_production' => 'warning',
                        'returned'      => 'primary',
                        'try_in'        => 'info',
                        'completed'     => 'success',
                        'rework'        => 'danger',
                    ];
                    $badge = $badges[$row->status] ?? 'default';
                    return '<span class="label label-' . $badge . '">' . __('lab_cases.status_' . $row->status) . '</span>';
                })
                ->addColumn('overdue_flag', function ($row) {
                    if (!empty($row->expected_return_date)
                        && in_array($row->status, ['sent', 'in_production'])
                        && empty($row->actual_return_date)
                        && $row->expected_return_date < now()->format('Y-m-d')
                    ) {
                        return '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i></span>';
                    }
                    return '';
                })
                ->addColumn('action', function ($row) {
                    return '
                    <div class="btn-group">
                        <button class="btn blue dropdown-toggle btn-sm" type="button" data-toggle="dropdown" aria-expanded="false">
                            ' . __('common.action') . ' <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="' . url('lab-cases/' . $row->id) . '">' . __('lab_cases.view_lab_case') . '</a></li>
                            <li><a href="#" onclick="editLabCase(' . $row->id . ')">' . __('lab_cases.edit_lab_case') . '</a></li>
                            <li><a href="#" onclick="updateStatus(' . $row->id . ')">' . __('lab_cases.update_status') . '</a></li>
                            <li><a href="' . url('print-lab-case/' . $row->id) . '" target="_blank">' . __('lab_cases.print_lab_case') . '</a></li>
                            <li class="divider"></li>
                            <li><a href="#" onclick="deleteLabCase(' . $row->id . ')" class="text-danger">' . __('lab_cases.delete') . '</a></li>
                        </ul>
                    </div>';
                })
                ->rawColumns(['lab_case_no', 'status_label', 'overdue_flag', 'action'])
                ->make(true);
        }

        $labs = $this->labService->getActiveLabsForSelect();
        return view('lab_cases.index', compact('labs'));
    }

    public function show($id)
    {
        $labCase = $this->labCaseService->getLabCase($id);

        if (!$labCase) {
            return redirect('lab-cases')->with('error', __('lab_cases.case_not_found'));
        }

        return view('lab_cases.show', compact('labCase'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id'           => 'required|exists:patients,id',
            'doctor_id'            => 'required|exists:users,id',
            'lab_id'               => 'required|exists:labs,id',
            'prosthesis_type'      => 'required|string|max:100',
            'material'             => 'nullable|string|max:100',
            'color_shade'          => 'nullable|string|max:50',
            'teeth_positions'      => 'nullable|string|max:255',
            'special_requirements' => 'nullable|string|max:2000',
            'expected_return_date' => 'nullable|date|after_or_equal:today',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $data = $request->only([
            'patient_id', 'doctor_id', 'lab_id', 'prosthesis_type',
            'material', 'color_shade', 'special_requirements',
            'expected_return_date', 'lab_fee', 'patient_charge', 'notes',
        ]);

        // Convert comma-separated teeth_positions to array
        if (!empty($request->teeth_positions)) {
            $data['teeth_positions'] = array_map('trim', explode(',', $request->teeth_positions));
        }

        $this->labCaseService->createLabCase($data);

        return response()->json(['message' => __('lab_cases.case_created'), 'status' => true]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'prosthesis_type'      => 'nullable|string|max:100',
            'material'             => 'nullable|string|max:100',
            'color_shade'          => 'nullable|string|max:50',
            'teeth_positions'      => 'nullable|string|max:255',
            'special_requirements' => 'nullable|string|max:2000',
            'expected_return_date' => 'nullable|date',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'quality_rating'       => 'nullable|integer|min:1|max:5',
            'notes'                => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $data = $request->only([
            'prosthesis_type', 'material', 'color_shade',
            'special_requirements', 'expected_return_date', 'lab_fee',
            'patient_charge', 'quality_rating', 'notes',
        ]);

        if (!empty($request->teeth_positions)) {
            $data['teeth_positions'] = array_map('trim', explode(',', $request->teeth_positions));
        }

        $result = $this->labCaseService->updateLabCase($id, $data);

        if (!$result) {
            return response()->json(['message' => __('lab_cases.error_updating_case'), 'status' => false]);
        }

        return response()->json(['message' => __('lab_cases.case_updated'), 'status' => true]);
    }

    public function destroy($id)
    {
        $result = $this->labCaseService->deleteLabCase($id);

        if (!$result) {
            return response()->json(['message' => __('lab_cases.error_deleting_case'), 'status' => false]);
        }

        return response()->json(['message' => __('lab_cases.case_deleted'), 'status' => true]);
    }

    /**
     * Update lab case status (AJAX).
     */
    public function updateStatus(Request $request, $id)
    {
        $validStatuses = implode(',', array_keys(LabCase::STATUSES));

        $validator = Validator::make($request->all(), [
            'status'        => "required|in:{$validStatuses}",
            'rework_reason' => 'nullable|required_if:status,rework|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $result = $this->labCaseService->updateStatus(
            $id,
            $request->input('status'),
            $request->only(['rework_reason', 'sent_date', 'actual_return_date'])
        );

        if (!$result) {
            return response()->json(['message' => __('lab_cases.error_updating_status'), 'status' => false]);
        }

        return response()->json(['message' => __('lab_cases.status_updated'), 'status' => true]);
    }

    /**
     * Get lab case detail as JSON (for edit modal).
     */
    public function getCase($id)
    {
        $case = $this->labCaseService->getLabCase($id);

        if (!$case) {
            return response()->json(['message' => __('lab_cases.case_not_found'), 'status' => false]);
        }

        return response()->json($case);
    }

    /**
     * Print lab case as PDF.
     */
    public function printLabCase($id)
    {
        $labCase = $this->labCaseService->getPrintData($id);

        if (!$labCase) {
            return redirect('lab-cases')->with('error', __('lab_cases.case_not_found'));
        }

        $pdf = PDF::loadView('lab_cases.print', compact('labCase'));
        return $pdf->stream('lab-case-' . $labCase->lab_case_no . '.pdf', ['Attachment' => false])
            ->header('Content-Type', 'application/pdf');
    }

    /**
     * Patient's lab cases for patient detail page.
     */
    public function patientLabCases(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->labCaseService->getPatientCases($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('lab_case_no', function ($row) {
                    return '<a href="' . url('lab-cases/' . $row->id) . '">' . $row->lab_case_no . '</a>';
                })
                ->addColumn('status_label', function ($row) {
                    $badges = [
                        'pending'       => 'default',
                        'sent'          => 'info',
                        'in_production' => 'warning',
                        'returned'      => 'primary',
                        'try_in'        => 'info',
                        'completed'     => 'success',
                        'rework'        => 'danger',
                    ];
                    $badge = $badges[$row->status] ?? 'default';
                    return '<span class="label label-' . $badge . '">' . __('lab_cases.status_' . $row->status) . '</span>';
                })
                ->rawColumns(['lab_case_no', 'status_label'])
                ->make(true);
        }

        return response()->json([]);
    }
}
