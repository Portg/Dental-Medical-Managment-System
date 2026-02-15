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
        'medical_case_id', 'lab_id', 'prosthesis_type', 'material',
        'color_shade', 'teeth_positions', 'special_requirements',
        'status', 'sent_date', 'expected_return_date', 'actual_return_date',
        'lab_fee', 'patient_charge', 'quality_rating',
        'rework_count', 'rework_reason', 'notes', '_who_added',
    ];

    protected $casts = [
        'teeth_positions'      => 'array',
        'lab_fee'              => 'decimal:2',
        'patient_charge'       => 'decimal:2',
        'sent_date'            => 'date',
        'expected_return_date' => 'date',
        'actual_return_date'   => 'date',
    ];

    public const STATUSES = [
        'pending'       => '待送出',
        'sent'          => '已送出',
        'in_production' => '制作中',
        'returned'      => '已返回',
        'try_in'        => '试戴',
        'completed'     => '完成',
        'rework'        => '返工',
    ];

    public const PROSTHESIS_TYPES = [
        'crown'            => '冠',
        'bridge'           => '桥',
        'removable'        => '活动义齿',
        'implant'          => '种植体',
        'veneer'           => '贴面',
        'inlay_onlay'      => '嵌体/高嵌体',
        'denture'          => '全口义齿',
        'orthodontic'      => '正畸器',
        'night_guard'      => '夜磨牙垫',
        'surgical_guide'   => '种植导板',
        'other'            => '其他',
    ];

    public const MATERIALS = [
        'zirconia'         => '氧化锆',
        'pfm'              => '金属烤瓷',
        'all_ceramic'      => '全瓷',
        'emax'             => 'E.max 铸瓷',
        'composite'        => '树脂',
        'metal'            => '金属',
        'acrylic'          => '丙烯酸',
        'titanium'         => '钛合金',
        'peek'             => 'PEEK',
        'other'            => '其他',
    ];

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

        return in_array($this->status, ['sent', 'in_production'])
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
}
