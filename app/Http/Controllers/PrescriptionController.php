<?php

namespace App\Http\Controllers;

use App\Services\PrescriptionService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use PDF;

class PrescriptionController extends Controller
{
    private PrescriptionService $prescriptionService;

    public function __construct(PrescriptionService $prescriptionService)
    {
        $this->prescriptionService = $prescriptionService;
    }

    /**
     * Display all prescriptions listing.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function listAll(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->prescriptionService->getAllPrescriptions();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="' . url('medical-treatment/' . $row->appointment_id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editPrescription(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deletePrescription(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn'])
                ->make(true);
        }

        return view('prescriptions.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $id)
    {
        if ($request->ajax()) {
            $data = $this->prescriptionService->getPrescriptionsByAppointment($id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editPrescription(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deletePrescription(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    public function filterDrugs(Request $request)
    {
        $data = $this->prescriptionService->getAllDrugNames();
        echo json_encode($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->prescriptionService->createPrescriptions($request->appointment_id, $request->addmore);
        return response()->json(['message' => __('messages.prescription_created_successfully'), 'status' => true]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Prescription $prescription
     * @return \Illuminate\Http\Response
     */
    public function show($prescription)
    {
        //
    }

    public function printPrescription($appointment_id)
    {
        $data = $this->prescriptionService->getPrintData($appointment_id);

        $pdf = PDF::loadView('medical_treatment.prescriptions.print_out', $data);
        return $pdf->stream('medium', array("attachment" => false))->header('Content-Type', 'application/pdf');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->prescriptionService->getPrescriptionForEdit($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $status = $this->prescriptionService->updatePrescription($id, $request->only('drug', 'qty', 'directions'));
        if ($status) {
            return response()->json(['message' => __('messages.prescription_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->prescriptionService->deletePrescription($id);
        if ($status) {
            return response()->json(['message' => __('messages.prescription_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
