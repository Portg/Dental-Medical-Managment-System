<?php

namespace App\Services;

use App\Patient;
use App\PatientImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientImageService
{
    /**
     * Get all patient images for DataTables.
     */
    public function getAllImages(): Collection
    {
        return DB::table('patient_images')
            ->leftJoin('patients', 'patients.id', 'patient_images.patient_id')
            ->leftJoin('users as added_by', 'added_by.id', 'patient_images._who_added')
            ->whereNull('patient_images.deleted_at')
            ->orderBy('patient_images.created_at', 'desc')
            ->select(
                'patient_images.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.patient_no',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(added_by.surname, added_by.othername) as added_by_name"
                    : "CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
            )
            ->get();
    }

    /**
     * Get images for a specific patient for DataTables.
     */
    public function getPatientImages(int $patientId): Collection
    {
        return DB::table('patient_images')
            ->leftJoin('users as added_by', 'added_by.id', 'patient_images._who_added')
            ->whereNull('patient_images.deleted_at')
            ->where('patient_images.patient_id', $patientId)
            ->orderBy('patient_images.image_date', 'desc')
            ->select(
                'patient_images.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(added_by.surname, added_by.othername) as added_by_name"
                    : "CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
            )
            ->get();
    }

    /**
     * Get all active patients for the dropdown.
     */
    public function getActivePatients(): Collection
    {
        return Patient::whereNull('deleted_at')->orderBy('surname')->get();
    }

    /**
     * Store a new patient image record.
     *
     * @param array  $data     Form data (title, image_type, description, tooth_number, image_date, patient_id, appointment_id, medical_case_id)
     * @param array  $fileInfo Uploaded file info (file_path, file_name, file_size, mime_type)
     * @param int    $userId   Current user ID
     */
    public function createImage(array $data, array $fileInfo, int $userId): ?PatientImage
    {
        return PatientImage::create([
            'image_no' => PatientImage::generateImageNo(),
            'title' => $data['title'],
            'image_type' => $data['image_type'],
            'file_path' => $fileInfo['file_path'],
            'file_name' => $fileInfo['file_name'],
            'file_size' => $fileInfo['file_size'],
            'mime_type' => $fileInfo['mime_type'],
            'description' => $data['description'] ?? null,
            'tooth_number' => $data['tooth_number'] ?? null,
            'image_date' => $data['image_date'],
            'patient_id' => $data['patient_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'medical_case_id' => $data['medical_case_id'] ?? null,
            '_who_added' => $userId,
        ]);
    }

    /**
     * Get a single image with relations.
     */
    public function getImageWithRelations(int $id): PatientImage
    {
        return PatientImage::with(['patient', 'addedBy'])->findOrFail($id);
    }

    /**
     * Get a single image for editing.
     */
    public function getImageForEdit(int $id): ?PatientImage
    {
        return PatientImage::where('id', $id)->first();
    }

    /**
     * Update a patient image record.
     *
     * @param int        $id         Image ID
     * @param array      $data       Form data
     * @param array|null $fileInfo   New file info if a replacement was uploaded
     */
    public function updateImage(int $id, array $data, ?array $fileInfo = null): bool
    {
        $image = PatientImage::findOrFail($id);

        $updateData = [
            'title' => $data['title'],
            'image_type' => $data['image_type'],
            'description' => $data['description'] ?? null,
            'tooth_number' => $data['tooth_number'] ?? null,
            'image_date' => $data['image_date'],
        ];

        if ($fileInfo) {
            // Delete old file
            if (file_exists(public_path($image->file_path))) {
                unlink(public_path($image->file_path));
            }

            $updateData['file_path'] = $fileInfo['file_path'];
            $updateData['file_name'] = $fileInfo['file_name'];
            $updateData['file_size'] = $fileInfo['file_size'];
            $updateData['mime_type'] = $fileInfo['mime_type'];
        }

        return (bool) $image->update($updateData);
    }

    /**
     * Delete a patient image (soft-delete) and remove its file.
     */
    public function deleteImage(int $id): bool
    {
        $image = PatientImage::findOrFail($id);

        if (file_exists(public_path($image->file_path))) {
            unlink(public_path($image->file_path));
        }

        return (bool) $image->delete();
    }
}
