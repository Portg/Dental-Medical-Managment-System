<?php

namespace App\Http\Controllers;

use App\PatientImage;
use App\Patient;
use App\MedicalCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class PatientImageController extends Controller
{
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
            $data = DB::table('patient_images')
                ->leftJoin('patients', 'patients.id', 'patient_images.patient_id')
                ->leftJoin('users as added_by', 'added_by.id', 'patient_images._who_added')
                ->whereNull('patient_images.deleted_at')
                ->orderBy('patient_images.created_at', 'desc')
                ->select(
                    'patient_images.*',
                    DB::raw("CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                    'patients.patient_no',
                    DB::raw("CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
                )
                ->get();

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

        $patients = Patient::whereNull('deleted_at')->orderBy('surname')->get();
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
            $data = DB::table('patient_images')
                ->leftJoin('users as added_by', 'added_by.id', 'patient_images._who_added')
                ->whereNull('patient_images.deleted_at')
                ->where('patient_images.patient_id', $patient_id)
                ->orderBy('patient_images.image_date', 'desc')
                ->select(
                    'patient_images.*',
                    DB::raw("CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
                )
                ->get();

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

            $status = PatientImage::create([
                'image_no' => PatientImage::generateImageNo(),
                'title' => $request->title,
                'image_type' => $request->image_type,
                'file_path' => $filePath . '/' . $fileName,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'description' => $request->description,
                'tooth_number' => $request->tooth_number,
                'image_date' => $request->image_date,
                'patient_id' => $request->patient_id,
                'appointment_id' => $request->appointment_id,
                'medical_case_id' => $request->medical_case_id,
                '_who_added' => Auth::User()->id
            ]);

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
        $image = PatientImage::with(['patient', 'addedBy'])->findOrFail($id);
        return response()->json($image);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $image = PatientImage::where('id', $id)->first();
        return response()->json($image);
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

        $image = PatientImage::findOrFail($id);

        $updateData = [
            'title' => $request->title,
            'image_type' => $request->image_type,
            'description' => $request->description,
            'tooth_number' => $request->tooth_number,
            'image_date' => $request->image_date,
        ];

        // Handle new file upload if provided
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');

            // Delete old file
            if (file_exists(public_path($image->file_path))) {
                unlink(public_path($image->file_path));
            }

            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = 'patient_images/' . $image->patient_id;
            $file->move(public_path($filePath), $fileName);

            $updateData['file_path'] = $filePath . '/' . $fileName;
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_size'] = $file->getSize();
            $updateData['mime_type'] = $file->getClientMimeType();
        }

        $status = $image->update($updateData);

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
        $image = PatientImage::findOrFail($id);

        // Delete the file
        if (file_exists(public_path($image->file_path))) {
            unlink(public_path($image->file_path));
        }

        $status = $image->delete();

        if ($status) {
            return response()->json(['message' => __('patient_images.image_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
