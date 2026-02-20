<?php

namespace App\Http\Controllers;

use App\Services\PatientImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PatientImageController extends Controller
{
    private PatientImageService $patientImageService;

    public function __construct(PatientImageService $patientImageService)
    {
        $this->patientImageService = $patientImageService;
        $this->middleware('can:edit-patients');
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

            return $this->patientImageService->buildIndexDataTable($data);
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
            $data = $this->patientImageService->getPatientImages((int) $patient_id);

            return $this->patientImageService->buildPatientImagesDataTable($data);
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
                $request->only(['title', 'patient_id', 'image_date', 'image_type']),
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
        return response()->json($this->patientImageService->getImageWithRelations((int) $id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->patientImageService->getImageForEdit((int) $id));
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
            $image = $this->patientImageService->getImageForEdit((int) $id);
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

        $status = $this->patientImageService->updateImage((int) $id, $request->only(['title', 'image_date', 'image_type']), $fileInfo);

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
        $status = $this->patientImageService->deleteImage((int) $id);

        if ($status) {
            return response()->json(['message' => __('patient_images.image_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
