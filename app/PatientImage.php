<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'image_no',
        'title',
        'image_type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'description',
        'tooth_number',
        'image_date',
        'patient_id',
        'appointment_id',
        'medical_case_id',
        '_who_added',
    ];

    protected $casts = [
        'image_date' => 'datetime',
    ];

    /**
     * Generate unique image number
     */
    public static function generateImageNo()
    {
        $year = date('Y');
        $lastImage = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastImage) {
            $lastNumber = intval(substr($lastImage->image_no, -5));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'IMG' . $year . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the patient that owns the image.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the appointment associated with the image.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the medical case associated with the image.
     */
    public function medicalCase()
    {
        return $this->belongsTo(MedicalCase::class, 'medical_case_id');
    }

    /**
     * Get the user who added the image.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
