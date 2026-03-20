<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class LabCase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lab_case_no', 'patient_id', 'doctor_id', 'appointment_id',
        'medical_case_id', 'lab_id', 'processing_days',
        'special_requirements',
        'status', 'sent_date', 'expected_return_date', 'actual_return_date',
        'lab_fee', 'patient_charge', 'quality_rating',
        'rework_count', 'rework_reason', 'notes', '_who_added',
    ];

    protected $casts = [
        'lab_fee'              => 'decimal:2',
        'patient_charge'       => 'decimal:2',
        'sent_date'            => 'date',
        'expected_return_date' => 'date',
        'actual_return_date'   => 'date',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_IN_PRODUCTION = 'in_production';
    const STATUS_RETURNED = 'returned';
    const STATUS_TRY_IN = 'try_in';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REWORK = 'rework';

    /**
     * 从字典表取状态列表 ['code' => 'name', ...]
     */
    public static function statusOptions(): array
    {
        return \App\DictItem::listByType('lab_case_status')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * 从字典表取修复体类型列表
     */
    public static function prosthesisTypeOptions(): array
    {
        return \App\DictItem::listByType('lab_case_prosthesis_type')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * 从字典表取材料列表
     */
    public static function materialOptions(): array
    {
        return \App\DictItem::listByType('lab_case_material')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * Generate a unique lab case number.
     */
    public static function generateCaseNo(): string
    {
        $prefix = 'LC' . date('Ymd');
        $last = DB::table('lab_cases')
            ->where('lab_case_no', 'like', $prefix . '%')
            ->orderBy('lab_case_no', 'desc')
            ->value('lab_case_no');

        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the case is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->expected_return_date || $this->actual_return_date) {
            return false;
        }

        return in_array($this->status, [self::STATUS_SENT, self::STATUS_IN_PRODUCTION])
            && now()->gt($this->expected_return_date);
    }

    /**
     * Calculate profit (patient charge - lab fee).
     */
    public function getProfitAttribute(): float
    {
        return (float) $this->patient_charge - (float) $this->lab_fee;
    }

    // ─── Relationships ───────────────────────────────────────────

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

    public function appointment()
    {
        return $this->belongsTo('App\Appointment', 'appointment_id');
    }

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function lab()
    {
        return $this->belongsTo('App\Lab', 'lab_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function items()
    {
        return $this->hasMany(LabCaseItem::class, 'lab_case_id')->orderBy('sort_order');
    }
}
