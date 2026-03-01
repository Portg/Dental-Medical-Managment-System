<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\MedicalCaseService;
use App\Services\OcrService;
use App\Services\PatientService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OcrRecognizeController extends Controller
{
    protected OcrService $ocrService;
    protected PatientService $patientService;
    protected MedicalCaseService $medicalCaseService;

    public function __construct(OcrService $ocrService, PatientService $patientService, MedicalCaseService $medicalCaseService)
    {
        $this->middleware('can:create-patients');
        $this->ocrService = $ocrService;
        $this->patientService = $patientService;
        $this->medicalCaseService = $medicalCaseService;
    }

    /**
     * Show OCR recognition page.
     */
    public function index()
    {
        $doctors = User::where('is_doctor', true)->whereNull('deleted_at')->orderBy('surname')->get();

        return view('ocr.recognize', compact('doctors'));
    }

    /**
     * Upload image and perform OCR recognition.
     */
    public function recognize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,bmp|max:10240',
        ], [
            'image.required' => __('ocr.upload_required'),
            'image.image'    => __('ocr.invalid_image'),
            'image.mimes'    => __('ocr.supported_formats'),
            'image.max'      => __('ocr.file_too_large'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status'  => 0,
            ]);
        }

        // Save to temporary location
        $file = $request->file('image');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $tempDir = storage_path('app/ocr_temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $file->move($tempDir, $fileName);
        $imagePath = $tempDir . '/' . $fileName;

        try {
            // Run OCR recognition
            $ocrResult = $this->ocrService->recognize($imagePath);

            // Parse structured fields from raw text
            $parsed = $this->ocrService->parseMedicalRecord($ocrResult['raw_text']);

            return response()->json([
                'status'  => 1,
                'message' => __('ocr.recognize_success'),
                'data'    => [
                    'patient'    => $parsed['patient'],
                    'case'       => $parsed['case'],
                    'raw_text'   => $ocrResult['raw_text'],
                    'confidence' => $ocrResult['confidence'],
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 0,
                'message' => __('ocr.ocr_error') . ': ' . $e->getMessage(),
            ]);
        } finally {
            // Clean up temporary file
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
    }

    /**
     * Create patient and/or medical case from OCR results.
     */
    public function createFromOcr(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_mode' => 'required|in:new,existing',
            'patient_id'   => 'required_if:patient_mode,existing|nullable|integer|exists:patients,id',
            'full_name'    => 'required_if:patient_mode,new|nullable|string|min:2',
            'gender'       => 'required_if:patient_mode,new|nullable|string',
            'telephone'    => 'required_if:patient_mode,new|nullable|string',
            'doctor_id'    => 'required|integer|exists:users,id',
            'case_date'    => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status'  => 0,
            ]);
        }

        try {
            $patientId = null;

            if ($request->input('patient_mode') === 'existing') {
                $patientId = $request->input('patient_id');
            } else {
                // Create new patient
                $patientInput = [
                    'full_name' => $request->input('full_name'),
                    'gender'    => $request->input('gender'),
                    'telephone' => $request->input('telephone'),
                    'dob'       => $request->input('dob'),
                    'age'       => $request->input('age'),
                    'address'   => $request->input('address'),
                    'nin'       => $request->input('nin'),
                    'blood_type' => $request->input('blood_type'),
                ];

                // Handle drug allergies
                if ($request->filled('drug_allergies_other')) {
                    $patientInput['drug_allergies_other'] = $request->input('drug_allergies_other');
                }

                $nameParts = $this->patientService->validateAndParseInput($patientInput);
                $patientData = $this->patientService->buildPatientData($patientInput, $nameParts, false);
                $patient = $this->patientService->createPatient($patientData);

                if (!$patient) {
                    return response()->json([
                        'message' => __('ocr.create_patient_failed'),
                        'status'  => 0,
                    ]);
                }

                $patientId = $patient->id;
            }

            // Build and create medical case
            $caseInput = [
                'patient_id'                 => $patientId,
                'doctor_id'                  => $request->input('doctor_id'),
                'case_date'                  => $request->input('case_date'),
                'chief_complaint'            => $request->input('chief_complaint'),
                'history_of_present_illness' => $request->input('history_of_present_illness'),
                'examination'                => $request->input('examination'),
                'auxiliary_examination'       => $request->input('auxiliary_examination'),
                'diagnosis'                  => $request->input('diagnosis'),
                'treatment'                  => $request->input('treatment'),
                'medical_orders'             => $request->input('medical_orders'),
                'visit_type'                 => 'initial',
            ];

            $caseData = $this->medicalCaseService->buildCaseData($caseInput, false);
            $case = $this->medicalCaseService->createCase($caseData, false);

            if (!$case) {
                return response()->json([
                    'message' => __('ocr.create_case_failed'),
                    'status'  => 0,
                ]);
            }

            return response()->json([
                'status'  => 1,
                'message' => __('ocr.create_success'),
                'data'    => [
                    'patient_id' => $patientId,
                    'case_id'    => $case->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 0,
            ]);
        }
    }
}
