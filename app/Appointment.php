<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class Appointment extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    const STATUS_WAITING = 'waiting';
    const STATUS_TREATMENT_COMPLETE = 'treatment complete';
    const STATUS_TREATMENT_INCOMPLETE = 'treatment incomplete';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_REJECTED = 'rejected';

    const VISIT_WALK_IN = 'walk_in';
    const VISIT_APPOINTMENT = 'appointment';
    const VISIT_SINGLE_TREATMENT = 'single treatment';
    const VISIT_REVIEW_TREATMENT = 'review treatment';

    protected $fillable = [
        'appointment_no', 'start_date', 'end_date', 'start_time',
        'duration_minutes', 'appointment_type', 'source',
        'notes', 'visit_information', 'status',
        'cancelled_reason', 'cancelled_by', 'no_show_count',
        'reminder_sent', 'reminder_sent_at', 'confirmed_by_patient', 'confirmed_at',
        'doctor_id', 'patient_id', 'branch_id', 'chair_id', 'service_id',
        'medical_case_id', 'shift_id', 'sort_by', '_who_added'
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'sort_by' => 'datetime:Y-m-d H:i',
        'reminder_sent_at' => 'datetime:Y-m-d H:i',
        'confirmed_at' => 'datetime:Y-m-d H:i',
        'reminder_sent' => 'boolean',
        'confirmed_by_patient' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo('App\User', 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function branch()
    {
        return $this->belongsTo('App\Branch', 'branch_id');
    }

    public function chair()
    {
        return $this->belongsTo('App\Chair', 'chair_id');
    }

    /**
     * 主项目 —— 这次预约勾选的第一个。全集见 services()。
     *
     * 保留这个单值字段是有意的反规范化，理由见 appointment_services 建表迁移。
     */
    public function service()
    {
        return $this->belongsTo('App\MedicalService', 'service_id');
    }

    /** 这次预约约的全部项目（权威），按勾选顺序 */
    public function services()
    {
        return $this->belongsToMany('App\MedicalService', 'appointment_services', 'appointment_id', 'medical_service_id')
            ->withPivot('sort_order')
            ->orderBy('appointment_services.sort_order');
    }

    /**
     * 「这次预约约了什么」的显示串，供列表与日历用。
     *
     * 做成子查询而不是 join + GROUP BY：调用它的六七个查询本身都带 join 和
     * 各自的 group 语义，塞一个 GROUP_CONCAT 进去要顺带改它们的 groupBy，
     * 改错一处就是整列数据重复或丢行。子查询对调用方是零影响的。
     *
     * @param string $alias 外层查询里 appointments 表的别名
     */
    public static function serviceNamesSubquery(string $alias = 'appointments'): \Illuminate\Database\Query\Expression
    {
        // $alias 只来自代码里的字面量（'a' / 'appointments'），不来自请求
        return \Illuminate\Support\Facades\DB::raw("(
            SELECT GROUP_CONCAT(aps_ms.name ORDER BY aps.sort_order SEPARATOR ', ')
            FROM appointment_services aps
            JOIN medical_services aps_ms ON aps_ms.id = aps.medical_service_id
            WHERE aps.appointment_id = {$alias}.id
        ) as service_names");
    }

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo('App\User', 'cancelled_by');
    }

    /**
     * 关联：满意度调查（一次就诊一份）
     *
     * SatisfactionSurveyService::sendBatch() 用 whereDoesntHave('satisfactionSurvey')
     * 排除已发过的预约；此前缺少本关联，该调用会直接抛 BadMethodCallException。
     */
    public function satisfactionSurvey()
    {
        return $this->hasOne('App\SatisfactionSurvey', 'appointment_id');
    }

    public function progressNotes()
    {
        return $this->hasMany('App\ProgressNote', 'appointment_id');
    }

    public function vitalSigns()
    {
        return $this->hasMany('App\VitalSign', 'appointment_id');
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'appointment_id');
    }

    public function treatmentMaterials()
    {
        return $this->hasMany('App\TreatmentMaterial', 'appointment_id');
    }

    public function stockOuts()
    {
        return $this->hasMany('App\StockOut', 'appointment_id');
    }

    public function shift()
    {
        return $this->belongsTo('App\Shift', 'shift_id');
    }

    /**
     * Scope for first visits
     */
    public function scopeFirstVisit($query)
    {
        return $query->where('appointment_type', 'first_visit');
    }

    /**
     * Scope for revisits
     */
    public function scopeRevisit($query)
    {
        return $query->where('appointment_type', 'revisit');
    }

    /**
     * Scope for today's appointments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_date', today());
    }

    /**
     * Scope for confirmed appointments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('confirmed_by_patient', true);
    }

    /**
     * Scope for no-shows
     */
    public function scopeNoShow($query)
    {
        return $query->where('status', self::STATUS_NO_SHOW);
    }

    /**
     * Check if reminder needs to be sent
     */
    public function needsReminder()
    {
        return !$this->reminder_sent
            && $this->status === self::STATUS_SCHEDULED
            && $this->start_date->isAfter(now());
    }

    /**
     * Mark as no-show and increment counter
     */
    public function markAsNoShow()
    {
        $this->status = self::STATUS_NO_SHOW;
        $this->no_show_count = ($this->no_show_count ?? 0) + 1;
        $this->save();

        // Also update patient's cumulative no-show count if needed
        return $this;
    }

    public static function AppointmentNo()
    {
        $latest = self::latest()->first();
        if (!$latest) {
            return date('Y') . "" . '0001';
        } else if ($latest->deleted_at != "null") {
            return time() + $latest->id + 1;
        } else {
            return date('Y') . "" . sprintf('%04d', $latest->id + 1);
        }
    }
}
