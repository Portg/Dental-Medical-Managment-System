<?php

namespace App\Http\Controllers;

use App\Services\PatientImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class PatientImageController extends Controller
{
    private PatientImageService $patientImageService;

    public function __construct(PatientImageService $patientImageService)
    {
        $this->patientImageService = $patientImageService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->patientImageService->getAllImages();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewImage(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editImage(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteImage(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('typeBadge', function ($row) {
                    $class = 'default';
                    if ($row->image_type == 'X-Ray') $class = 'primary';
                    elseif ($row->image_type == 'CT') $class = 'warning';
                    elseif ($row->image_type == 'Intraoral') $class = 'success';
                    elseif ($row->image_type == 'Extraoral') $class = 'info';
                    return '<span class="label label-' . $class . '">' . __('patient_images.type_' . strtolower(str_replace('-', '_', $row->image_type))) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'typeBadge'])
                ->make(true);
        }

        $patients = $this->patientImageService->getActivePatients();
        return view('patient_images.index', compact('patients'));
    }

    /**
     * Display images for a specific patient.
     *
     * @param Request $request
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function patientImages(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->patientImageService->getPatientImages($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewImage(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editImage(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteImage(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('typeBadge', function ($row) {
                    $class = 'default';
                    if ($row->image_type == 'X-Ray') $class = 'primary';
                    elseif ($row->image_type == 'CT') $class = 'warning';
                    elseif ($row->image_type == 'Intraoral') $class = 'success';
                    elseif ($row->image_type == 'Extraoral') $class = 'info';
                    return '<span class="label label-' . $class . '">' . __('patient_images.type_' . strtolower(str_replace('-', '_', $row->image_type))) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'typeBadge'])
                ->make(true);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'patient_id' => 'required|exists:patients,id',
            'image_date' => 'required|date',
            'image_type' => 'required|in:X-Ray,CT,Intraoral,Extraoral,Other',
            'image_file' => 'required|image|mimes:jpeg,png,jpg,gif,bmp|max:10240',
        ], [
            'title.required' => __('validation.custom.title.required'),
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'image_date.required' => __('validation.custom.image_date.required'),
            'image_type.required' => __('validation.custom.image_type.required'),
            'image_file.required' => __('validation.custom.image_file.required'),
            'image_file.image' => __('validation.custom.image_file.image'),
            'image_file.max' => __('validation.custom.image_file.max'),
        ])->validate();

        // Handle file upload
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = 'patient_images/' . $request->patient_id;
            $file->move(public_path($filePath), $fileName);

            $fileInfo = [
                'file_path' => $filePath . '/' . $fileName,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
            ];

            $status = $this->patientImageService->createImage(
                $request->all(),
                $fileInfo,
                Auth::User()->id
            );

            if ($status) {
                return response()->json([
                    'message' => __('patient_images.image_uploaded_successfully'),
                    'status' => true,
                    'data' => $status
                ]);
            }
        }

        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->patientImageService->getImageWithRelations($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->patientImageService->getImageForEdit($id));
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
        Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'image_date' => 'required|date',
            'image_type' => 'required|in:X-Ray,CT,Intraoral,Extraoral,Other',
        ], [
            'title.required' => __('validation.custom.title.required'),
            'image_date.required' => __('validation.custom.image_date.required'),
            'image_type.required' => __('validation.custom.image_type.required'),
        ])->validate();

        $fileInfo = null;

        // Handle new file upload if provided
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $image = $this->patientImageService->getImageForEdit($id);
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = 'patient_images/' . $image->patient_id;
            $file->move(public_path($filePath), $fileName);

            $fileInfo = [
                'file_path' => $filePath . '/' . $fileName,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
            ];
        }

        $status = $this->patientImageService->updateImage($id, $request->all(), $fileInfo);

        if ($status) {
            return response()->json(['message' => __('patient_images.image_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->patientImageService->deleteImage($id);

        if ($status) {
            return response()->json(['message' => __('patient_images.image_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
