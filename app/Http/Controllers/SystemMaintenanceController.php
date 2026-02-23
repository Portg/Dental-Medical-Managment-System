<?php

namespace App\Http\Controllers;

use App\AccessLog;
use App\OperationLog;
use App\Services\SystemMaintenanceService;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Yajra\DataTables\DataTables;

class SystemMaintenanceController extends Controller
{
    private SystemMaintenanceService $service;

    public function __construct(SystemMaintenanceService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-system-maintenance');
    }

    public function index()
    {
        $backups         = $this->service->getBackupList();
        $retentionConfig = $this->service->getRetentionConfig();
        $users           = User::select('id', 'surname', 'othername')->orderBy('surname')->get();

        return view('system_maintenance.index', compact('backups', 'retentionConfig', 'users'));
    }

    public function triggerBackup(): JsonResponse
    {
        try {
            $exitCode = Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();

            if ($exitCode !== 0) {
                return response()->json([
                    'message' => __('system_maintenance.backup_failed'),
                    'status'  => 0,
                    'output'  => $output,
                ]);
            }

            return response()->json([
                'message' => __('system_maintenance.backup_started_successfully'),
                'status'  => 1,
                'output'  => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('system_maintenance.backup_failed') . ': ' . $e->getMessage(),
                'status'  => 0,
            ]);
        }
    }

    public function downloadBackup(string $file)
    {
        if (!$this->service->isValidFilename($file)) {
            abort(400, __('system_maintenance.invalid_filename'));
        }

        $path = $this->service->getBackupPath($file);
        if (!$path) {
            abort(404, __('system_maintenance.backup_not_found'));
        }

        return response()->download($path, $file);
    }

    public function deleteBackup(string $file): JsonResponse
    {
        if (!$this->service->isValidFilename($file)) {
            return response()->json([
                'message' => __('system_maintenance.invalid_filename'),
                'status'  => 0,
            ]);
        }

        $deleted = $this->service->deleteBackup($file);

        return response()->json([
            'message' => $deleted
                ? __('system_maintenance.backup_deleted_successfully')
                : __('system_maintenance.backup_not_found'),
            'status' => $deleted ? 1 : 0,
        ]);
    }

    public function triggerRetention(Request $request): JsonResponse
    {
        $dryRun = $request->boolean('dry_run', true);

        try {
            Artisan::call('retention:enforce', $dryRun ? ['--dry-run' => true] : []);
            $output = Artisan::output();

            return response()->json([
                'message' => $dryRun
                    ? __('system_maintenance.retention_dry_run_complete')
                    : __('system_maintenance.retention_executed_successfully'),
                'status' => 1,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('system_maintenance.retention_failed') . ': ' . $e->getMessage(),
                'status'  => 0,
            ]);
        }
    }

    public function operationLogs(Request $request)
    {
        $query = OperationLog::with('user')
            ->when($request->get('user_id'), fn ($q, $v) => $q->forUser($v))
            ->when($request->get('module'), fn ($q, $v) => $q->forModule($v))
            ->when($request->get('resource_type'), fn ($q, $v) => $q->forResource($v))
            ->when($request->get('start_date'), fn ($q, $v) => $q->where('operation_time', '>=', $v))
            ->when($request->get('end_date'), fn ($q, $v) => $q->where('operation_time', '<=', $v . ' 23:59:59'))
            ->orderBy('operation_time', 'desc');

        return DataTables::of($query)
            ->addColumn('user_name', fn ($row) => optional($row->user)->surname ?? '-')
            ->editColumn('operation_time', fn ($row) => $row->operation_time ? $row->operation_time->format('Y-m-d H:i:s') : '-')
            ->make(true);
    }

    public function accessLogs(Request $request)
    {
        $query = AccessLog::with('user')
            ->when($request->get('user_id'), fn ($q, $v) => $q->forUser($v))
            ->when($request->get('resource_type'), fn ($q, $v) => $q->forResource($v))
            ->when($request->get('start_date'), fn ($q, $v) => $q->where('access_time', '>=', $v))
            ->when($request->get('end_date'), fn ($q, $v) => $q->where('access_time', '<=', $v . ' 23:59:59'))
            ->orderBy('access_time', 'desc');

        return DataTables::of($query)
            ->addColumn('user_name', fn ($row) => optional($row->user)->surname ?? '-')
            ->editColumn('access_time', fn ($row) => $row->access_time ? $row->access_time->format('Y-m-d H:i:s') : '-')
            ->make(true);
    }

    public function auditLogs(Request $request)
    {
        $query = \OwenIt\Auditing\Models\Audit::with('user')
            ->when($request->get('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->get('auditable_type'), fn ($q, $v) => $q->where('auditable_type', $v))
            ->when($request->get('event'), fn ($q, $v) => $q->where('event', $v))
            ->when($request->get('start_date'), fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->get('end_date'), fn ($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'))
            ->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->addColumn('user_name', fn ($row) => optional($row->user)->surname ?? '-')
            ->editColumn('auditable_type', fn ($row) => str_replace('App\\', '', $row->auditable_type))
            ->editColumn('old_values', fn ($row) => $row->old_values ? json_encode($row->old_values, JSON_UNESCAPED_UNICODE) : '-')
            ->editColumn('new_values', fn ($row) => $row->new_values ? json_encode($row->new_values, JSON_UNESCAPED_UNICODE) : '-')
            ->editColumn('created_at', fn ($row) => $row->created_at->format('Y-m-d H:i:s'))
            ->make(true);
    }
}
