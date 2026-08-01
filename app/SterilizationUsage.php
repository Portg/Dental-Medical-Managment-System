<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class SterilizationUsage extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'record_id', 'appointment_id', 'patient_id', 'used_by',
        'used_at', 'notes',
        'patient_name', 'doctor_name', 'kit_name', 'batch_no',
    ];

    protected $casts = ['used_at' => 'datetime:Y-m-d H:i'];

    public function record()
    {
        return $this->belongsTo(SterilizationRecord::class, 'record_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
