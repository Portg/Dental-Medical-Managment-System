<?php

namespace App\Http\Controllers\Api\V1;

use App\DentalChart;
use App\Http\Resources\DentalChartResource;
use App\Services\DentalChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Dental Charts
 */
class DentalChartController extends ApiController
{
    public function __construct(
        protected DentalChartService $service
    ) {
        $this->middleware('can:edit-patients');
    }

    public function index(Request $request): JsonResponse
    {
        $list = $this->service->getPatientChartList()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $list->items(),
            'message' => 'OK',
            'meta'    => [
                'current_page' => $list->currentPage(),
                'last_page'    => $list->lastPage(),
                'per_page'     => $list->perPage(),
                'total'        => $list->total(),
            ],
        ]);
    }

    public function patientChart(int $patientId): JsonResponse
    {
        $appointment = $this->service->getLatestAppointment($patientId);

        if (!$appointment) {
            return $this->success([]);
        }

        $charts = $this->service->getChartByAppointment($appointment->id);

        return $this->success($charts);
    }

    public function appointmentChart(int $appointmentId): JsonResponse
    {
        $charts = $this->service->getChartByAppointment($appointmentId);

        return $this->success($charts);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment_id'        => 'required|exists:appointments,id',
            'chart_data'            => 'required|array',
            'chart_data.*.tooth'    => 'required|string|max:10',
            'chart_data.*.section'  => 'nullable|string|max:50',
            'chart_data.*.color'    => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->replaceChartData(
            $request->input('appointment_id'),
            $request->input('chart_data')
        );

        if (!$status) {
            return $this->error('Failed to save dental chart data', 500);
        }

        return $this->success(null, 'Dental chart saved', 201);
    }
}
