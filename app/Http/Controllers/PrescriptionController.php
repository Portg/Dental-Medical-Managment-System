<?php

namespace App\Http\Controllers;

use App\Services\PrescriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
use PDF;

class PrescriptionController extends Controller
{
    private PrescriptionService $prescriptionService;

    public function __construct(PrescriptionService $prescriptionService)
    {
        $this->prescriptionService = $prescriptionService;
        $this->middleware('can:manage-treatments');
    }

    /**
     * Display all prescriptions listing.
     */
    public function listAll(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->prescriptionService->getAllPrescriptions();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('status_label', fn ($row) => $this->renderStatusLabel($row->status))
                ->addColumn('action', function ($row) {
                    $btns = '<a href="#" onclick="viewPrescription(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a> ';
                    $btns .= '<a href="#" onclick="editPrescription(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a> ';
                    if (is_null($row->invoice_id)) {
                        $btns .= '<a href="#" onclick="deletePrescription(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                    }
                    return $btns;
                })
                ->rawColumns(['status_label', 'action'])
                ->make(true);
        }

        return view('prescriptions.index');
    }

    /**
     * Get prescriptions for a patient (patient detail tab).
     */
    public function patientPrescriptions(Request $request, int $patientId)
    {
        if ($request->ajax()) {
            $data = $this->prescriptionService->getPatientPrescriptions($patientId);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('status_label', fn ($row) => $this->renderStatusLabel($row->status))
                ->addColumn('action', function ($row) {
                    $btns = '<a href="#" onclick="viewPrescription(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a> ';
                    $btns .= '<a href="#" onclick="editPrescription(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a> ';
                    $btns .= '<a href="#" onclick="printPrescription(' . $row->id . ')" class="btn btn-default btn-sm"><i class="fa fa-print"></i></a> ';
                    if (is_null($row->invoice_id)) {
                        $btns .= '<a href="#" onclick="settlePrescription(' . $row->id . ')" class="btn btn-success btn-sm">' . __('prescriptions.settle') . '</a> ';
                        $btns .= '<a href="#" onclick="deletePrescription(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                    }
                    return $btns;
                })
                ->rawColumns(['status_label', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    /**
     * Get prescriptions for a specific appointment (legacy).
     */
    public function index(Request $request, $id)
    {
        if ($request->ajax()) {
            $data = $this->prescriptionService->getPrescriptionsByAppointment((int) $id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editPrescription(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deletePrescription(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    /**
     * Get available prescription services for selection.
     */
    public function prescriptionServices()
    {
        $services = $this->prescriptionService->getPrescriptionServices();
        return response()->json(['status' => true, 'data' => $services]);
    }

    /**
     * Get pending prescriptions for a patient (billing page selection).
     */
    public function pendingPrescriptions(int $patientId)
    {
        $prescriptions = $this->prescriptionService->getPendingPrescriptions($patientId);
        return response()->json(['status' => true, 'data' => $prescriptions]);
    }

    /**
     * Get drug names for autocomplete (legacy).
     */
    public function filterDrugs(Request $request)
    {
        $data = $this->prescriptionService->getAllDrugNames();
        echo json_encode($data);
    }

    /**
     * Store a new prescription.
     */
    public function store(Request $request)
    {
        // New flow: structured items with medical_service_id
        if ($request->has('items')) {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'items' => 'required|array|min:1',
                'items.*.medical_service_id' => 'required|exists:medical_services,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
            }

            $settle = $request->boolean('settle', false);
            $data = $request->only(['patient_id', 'doctor_id', 'medical_case_id', 'prescription_date', 'notes']);

            if ($settle) {
                $result = $this->prescriptionService->saveAndSettle($data, $request->input('items'));
            } else {
                $result = $this->prescriptionService->createPrescription($data, $request->input('items'));
            }

            return response()->json($result);
        }

        // Legacy flow: simple drug/qty/directions via appointment
        $this->prescriptionService->createPrescriptions((int) $request->appointment_id, $request->addmore);
        return response()->json(['message' => __('messages.prescription_created_successfully'), 'status' => true]);
    }

    /**
     * Show prescription detail.
     */
    public function show(int $id)
    {
        $prescription = $this->prescriptionService->getPrescriptionDetail($id);
        if (!$prescription) {
            return response()->json(['message' => __('messages.not_found'), 'status' => false], 404);
        }
        return response()->json(['status' => true, 'data' => $prescription]);
    }

    /**
     * Settle an existing pending prescription.
     */
    public function settle(int $id)
    {
        $result = $this->prescriptionService->settlePrescription($id);
        return response()->json($result);
    }

    /**
     * Print prescription PDF.
     */
    public function printPrescription($id)
    {
        // Try new flow first (by prescription ID)
        $prescription = \App\Prescription::find($id);

        if ($prescription) {
            $data = $this->prescriptionService->getPrintData($id);
            $pdf = PDF::loadView('prescriptions.print', $data);
            return $pdf->stream('prescription', ["attachment" => false])->header('Content-Type', 'application/pdf');
        }

        // Legacy: by appointment ID
        $data = $this->prescriptionService->getPrintDataByAppointment((int) $id);
        $pdf = PDF::loadView('medical_treatment.prescriptions.print_out', $data);
        return $pdf->stream('medium', ["attachment" => false])->header('Content-Type', 'application/pdf');
    }

    /**
     * Get prescription for editing.
     */
    public function edit($id)
    {
        $prescription = $this->prescriptionService->getPrescriptionForEdit((int) $id);
        return response()->json($prescription);
    }

    /**
     * Update a prescription.
     */
    public function update(Request $request, $id)
    {
        $data = $request->only(['doctor_id', 'prescription_date', 'notes']);
        $items = $request->input('items', []);

        $result = $this->prescriptionService->updatePrescription((int) $id, $data, $items);
        return response()->json($result);
    }

    /**
     * Delete a prescription (AG-023: blocked if has Invoice).
     */
    public function destroy($id)
    {
        $result = $this->prescriptionService->deletePrescription((int) $id);
        return response()->json($result);
    }

    private function renderStatusLabel(?string $status): string
    {
        $map = [
            'pending'      => ['label-warning', __('prescriptions.pending')],
            'filled'       => ['label-info', __('prescriptions.filled')],
            'completed'    => ['label-success', __('prescriptions.completed')],
            'discontinued' => ['label-default', __('prescriptions.discontinued')],
            'on_hold'      => ['label-default', __('prescriptions.on_hold')],
        ];
        $s = $map[$status] ?? ['label-default', $status ?? ''];
        return '<span class="label ' . $s[0] . '">' . $s[1] . '</span>';
    }
}
