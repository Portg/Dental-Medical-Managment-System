<?php

namespace App\Http\Controllers;

use App\Services\WorkLogService;
use App\User;
use App\WorkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class WorkLogOcrController extends Controller
{
    protected WorkLogService $workLogService;

    public function __construct(WorkLogService $workLogService)
    {
        $this->middleware('can:create-patients');
        $this->workLogService = $workLogService;
    }

    /**
     * Upload + recognition page.
     */
    public function index()
    {
        $doctors = User::where('is_doctor', true)
            ->whereNull('deleted_at')
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('surname')
            ->get();

        return view('work-log.recognize', compact('doctors'));
    }

    /**
     * Upload a work-log table image and run table OCR.
     */
    public function recognize(Request $request)
    {
        // When the photo exceeds PHP's upload_max_filesize / post MAX_FILE_SIZE,
        // the file arrives with an upload error and Laravel's implicit `uploaded`
        // rule yields a cryptic ":attribute 上传失败。". Surface the real cause
        // (server-side size limit) so the operator knows what to change.
        $uploaded = $request->file('image');
        if ($uploaded !== null && !$uploaded->isValid()
            && in_array($uploaded->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return response()->json([
                'status'  => 0,
                'message' => __('work_log.file_exceeds_server_limit', [
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size'       => ini_get('post_max_size'),
                ]),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,bmp|max:8192',
        ], [
            'image.required' => __('work_log.upload_required'),
            'image.image'    => __('work_log.invalid_image'),
            'image.mimes'    => __('work_log.supported_formats'),
            'image.max'      => __('work_log.file_too_large'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status'  => 0,
            ]);
        }

        $file     = $request->file('image');
        $fileName = time() . '_' . preg_replace('/[^\w.\-]/', '_', $file->getClientOriginalName());
        $storeDir = storage_path('app/work_log_images');

        if (!is_dir($storeDir)) {
            mkdir($storeDir, 0755, true);
        }

        $file->move($storeDir, $fileName);
        $imagePath = $storeDir . '/' . $fileName;

        try {
            $result = $this->workLogService->recognize($imagePath);

            return response()->json([
                'status'  => 1,
                'message' => __('work_log.recognize_success'),
                'data'    => [
                    'header_found'   => $result['header_found'],
                    'columns'        => $result['columns'],
                    'rows'           => $result['rows'],
                    'raw_lines'      => $result['raw_lines'],
                    'confidence'     => $result['avg_confidence'],
                    'source_image'   => 'work_log_images/' . $fileName,
                ],
            ]);
        } catch (\RuntimeException $e) {
            @unlink($imagePath);
            return response()->json([
                'status'  => 0,
                'message' => __('work_log.ocr_error') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Batch-persist reviewed rows (all-or-nothing transaction).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rows'              => 'required|array|min:1',
            'rows.*.patient_name' => 'nullable|string|max:100',
            'link_patients'     => 'nullable|boolean',
            'generate_invoices' => 'nullable|boolean',
            'year'              => 'nullable|integer|min:2000|max:2100',
            'source_image'      => 'nullable|string|max:255',
        ], [
            'rows.required' => __('work_log.no_rows'),
            'rows.min'      => __('work_log.no_rows'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status'  => 0,
            ]);
        }

        try {
            $summary = $this->workLogService->batchStore(
                $request->input('rows'),
                [
                    'link_patients'     => $request->boolean('link_patients'),
                    'generate_invoices' => $request->boolean('generate_invoices'),
                    'year'              => $request->input('year'),
                    'source_image'      => $request->input('source_image'),
                ]
            );

            return response()->json([
                'status'  => 1,
                'message' => __('work_log.save_success', [
                    'created'  => $summary['created'],
                    'linked'   => $summary['linked'],
                    'invoiced' => $summary['invoiced'],
                ]),
                'data'    => $summary,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Saved work-log list page + DataTables source.
     */
    public function list(Request $request)
    {
        if ($request->ajax()) {
            $query = WorkLog::with(['patient', 'doctor', 'invoice'])
                ->whereNull('deleted_at')
                ->orderByDesc('log_date')
                ->orderByDesc('id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('log_date', fn ($row) => $row->log_date ? $row->log_date->format('Y-m-d') : '-')
                ->editColumn('amount', fn ($row) => $row->amount !== null ? number_format((float) $row->amount, 2) : '-')
                ->addColumn('visit_type_label', function ($row) {
                    if ($row->visit_type === WorkLog::VISIT_INITIAL) {
                        return __('work_log.visit_initial');
                    }
                    if ($row->visit_type === WorkLog::VISIT_REVISIT) {
                        return __('work_log.visit_revisit');
                    }
                    return '-';
                })
                ->addColumn('doctor_label', function ($row) {
                    if ($row->doctor) {
                        return $row->doctor->surname . $row->doctor->othername;
                    }
                    return $row->doctor_name_raw ?: '-';
                })
                ->addColumn('patient_link', function ($row) {
                    if ($row->patient_id) {
                        $url = url('patients/' . $row->patient_id);
                        return '<a href="' . $url . '">' . e($row->patient_name) . '</a>';
                    }
                    return e($row->patient_name);
                })
                ->addColumn('invoice_label', function ($row) {
                    if ($row->invoice_id && $row->invoice) {
                        return e($row->invoice->invoice_no);
                    }
                    return '-';
                })
                ->rawColumns(['patient_link'])
                ->make(true);
        }

        return view('work-log.index');
    }
}
