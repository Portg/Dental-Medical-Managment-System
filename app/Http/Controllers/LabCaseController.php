<?php

namespace App\Http\Controllers;

use App\LabCase;
use App\Services\LabCaseService;
use App\Services\LabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PDF;

class LabCaseController extends Controller
{
    public function __construct(
        private LabCaseService $labCaseService,
        private LabService $labService,
    ) {
        $this->middleware('can:manage-labs');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only(['status', 'lab_id', 'doctor_id']);
            $filters['search'] = $request->input('search.value');
            $data = $this->labCaseService->getLabCaseList($filters);

            return $this->labCaseService->buildIndexDataTable($data);
        }

        $labs = $this->labService->getActiveLabsForSelect();
        return view('lab_cases.index', compact('labs'));
    }

    public function show($id)
    {
        $labCase = $this->labCaseService->getLabCase((int) $id);

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
            'processing_days'      => 'nullable|integer|min:1|max:365',
            'special_requirements' => 'nullable|string|max:2000',
            'sent_date'            => 'nullable|date',
            'expected_return_date' => 'nullable|date',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:2000',
            'items'                => 'required|array|min:1|max:4',
            'items.*.prosthesis_type' => 'required|string|max:100',
            'items.*.material'        => 'nullable|string|max:100',
            'items.*.color_shade'     => 'nullable|string|max:50',
            'items.*.teeth_positions' => 'nullable|string|max:255',
            'items.*.qty'             => 'nullable|integer|min:1|max:99',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $data = $request->only([
            'patient_id', 'doctor_id', 'lab_id', 'processing_days',
            'special_requirements', 'sent_date',
            'expected_return_date', 'lab_fee', 'patient_charge', 'notes',
        ]);

        // Build items array
        $items = [];
        foreach ($request->input('items', []) as $item) {
            $row = [
                'prosthesis_type' => $item['prosthesis_type'],
                'material'        => $item['material'] ?? null,
                'color_shade'     => $item['color_shade'] ?? null,
                'qty'             => $item['qty'] ?? 1,
            ];
            if (!empty($item['teeth_positions'])) {
                $row['teeth_positions'] = array_map('trim', explode(',', $item['teeth_positions']));
            }
            $items[] = $row;
        }

        $this->labCaseService->createLabCase($data, $items);

        return response()->json(['message' => __('lab_cases.case_created'), 'status' => true]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'processing_days'      => 'nullable|integer|min:1|max:365',
            'special_requirements' => 'nullable|string|max:2000',
            'expected_return_date' => 'nullable|date',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'quality_rating'       => 'nullable|integer|min:1|max:5',
            'notes'                => 'nullable|string|max:2000',
            'items'                => 'nullable|array|min:1|max:4',
            'items.*.prosthesis_type' => 'required_with:items|string|max:100',
            'items.*.material'        => 'nullable|string|max:100',
            'items.*.color_shade'     => 'nullable|string|max:50',
            'items.*.teeth_positions' => 'nullable|string|max:255',
            'items.*.qty'             => 'nullable|integer|min:1|max:99',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $data = $request->only([
            'processing_days', 'special_requirements', 'expected_return_date',
            'lab_fee', 'patient_charge', 'quality_rating', 'notes',
        ]);

        // Build items array if provided
        $items = null;
        if ($request->has('items')) {
            $items = [];
            foreach ($request->input('items', []) as $item) {
                $row = [
                    'prosthesis_type' => $item['prosthesis_type'],
                    'material'        => $item['material'] ?? null,
                    'color_shade'     => $item['color_shade'] ?? null,
                    'qty'             => $item['qty'] ?? 1,
                ];
                if (!empty($item['teeth_positions'])) {
                    $row['teeth_positions'] = array_map('trim', explode(',', $item['teeth_positions']));
                }
                $items[] = $row;
            }
        }

        $result = $this->labCaseService->updateLabCase((int) $id, $data, $items);

        if (!$result) {
            return response()->json(['message' => __('lab_cases.error_updating_case'), 'status' => false]);
        }

        return response()->json(['message' => __('lab_cases.case_updated'), 'status' => true]);
    }

    public function destroy($id)
    {
        $result = $this->labCaseService->deleteLabCase((int) $id);

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
        $validStatuses = \App\DictItem::listByType('lab_case_status')->pluck('code')->implode(',');

        $validator = Validator::make($request->all(), [
            'status'        => "required|in:{$validStatuses}",
            'rework_reason' => 'nullable|required_if:status,rework|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $result = $this->labCaseService->updateStatus(
            (int) $id,
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
        $case = $this->labCaseService->getLabCase((int) $id);

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
        $labCase = $this->labCaseService->getPrintData((int) $id);

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
            $data = $this->labCaseService->getPatientCases((int) $patient_id);

            return $this->labCaseService->buildPatientLabCasesDataTable($data);
        }

        return response()->json([]);
    }
}
