<?php

namespace App\Http\Controllers;

use App\Services\DentalChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

class DentalChartController extends Controller
{
    private DentalChartService $service;

    public function __construct(DentalChartService $service)
    {
        $this->service = $service;
        // 菜单配 manage-medical-cases，患者详情入口常只有 edit-patients —— 任一即可
        $this->middleware(function ($request, $next) {
            if (!Gate::any(['edit-patients', 'manage-medical-cases'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getPatientChartList();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('last_updated', function ($row) {
                    return $row->last_updated ? date('Y-m-d H:i', strtotime($row->last_updated)) : '-';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . url('dental-charting/for-patient/' . $row->patient_id) . '" class="btn btn-sm btn-primary">
                        <i class="fa fa-edit"></i> ' . __('odontogram.open_chart') . '
                    </a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('dental_chart.index');
    }

    /**
     * Open chart editor for a patient (resolve/create appointment behind the scenes).
     */
    public function openForPatient($patientId)
    {
        try {
            $appointment = $this->service->resolveAppointmentForChart((int) $patientId);
        } catch (\RuntimeException $e) {
            return redirect('dental-charting')->with('error', $e->getMessage());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect('dental-charting')->with('error', __('odontogram.patient_not_found'));
        }

        return redirect('dental-charting/open/' . $appointment->id);
    }

    /**
     * Standalone chart editor page for an appointment.
     */
    public function open($appointmentId)
    {
        $patient = $this->service->getPatientForChart((int) $appointmentId);
        if (!$patient) {
            return redirect('dental-charting')->with('error', __('odontogram.patient_not_found'));
        }

        return view('dental_chart.create', [
            'patient' => $patient,
            'appointment_id' => (int) $appointmentId,
        ]);
    }

    /**
     * Legacy create entry — redirect into the open editor.
     */
    public function create(Request $request, $appointment_id = null)
    {
        $appointmentId = (int) ($appointment_id ?: $request->query('appointment_id'));
        if ($appointmentId > 0) {
            return redirect('dental-charting/open/' . $appointmentId);
        }

        return redirect('dental-charting');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $appointmentId = (int) $request->appointment_id;
        if ($appointmentId <= 0 || !is_array($request->data)) {
            return response()->json(['message' => __('odontogram.patient_not_found'), 'success' => false], 422);
        }

        try {
            $this->service->replaceChartData($appointmentId, $request->data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'success' => false], 404);
        }

        return response()->json(['message' => __('odontogram.chart_saved_success'), 'success' => true]);
    }

    /**
     * Display chart JSON for an appointment (used by odontogram JS).
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->service->getChartByAppointment((int) $id));
    }

    public function edit($id)
    {
        return redirect('dental-charting/open/' . (int) $id);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
