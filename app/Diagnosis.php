<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use App\Concerns\SerializesDatesInAppTimezone;

class Diagnosis extends Model implements AuditableContract
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes, Auditable;

    protected $auditExclude = ['updated_at', 'created_at'];

    public function generateTags(): array
    {
        return ['medical-record'];
    }

    const STATUS_ACTIVE = 'Active';
    const STATUS_RESOLVED = 'Resolved';
    const STATUS_CHRONIC = 'Chronic';

    protected $table = 'diagnoses';

    protected $fillable = [
        'diagnosis_name', 'tooth_no', 'sort_order', 'icd_code', 'diagnosis_date', 'status',
        'severity', 'notes', 'resolved_date',
        'medical_case_id', 'patient_id', '_who_added'
    ];

    /**
     * 「这颗牙历次诊断过什么」—— 与 MedicalCaseItem::forTooth() 同一个用途。
     */
    public function scopeForTooth($query, string $toothNo)
    {
        return $query->where('tooth_no', $toothNo);
    }

    protected $casts = [
        'diagnosis_date' => 'datetime:Y-m-d H:i',
        'resolved_date' => 'datetime:Y-m-d H:i',
    ];

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
