<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SatisfactionSurvey extends Model
{
    use SoftDeletes;

    protected $table = 'satisfaction_surveys';

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'doctor_id',
        'branch_id',
        'overall_rating',
        'service_rating',
        'environment_rating',
        'wait_time_rating',
        'doctor_rating',
        'would_recommend',
        'feedback',
        'suggestions',
        'survey_channel',
        'survey_date',
        'is_anonymous',
        'status'
    ];

    protected $dates = [
        'survey_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED = 'expired';

    // 渠道常量
    const CHANNEL_SMS = 'sms';
    const CHANNEL_WECHAT = 'wechat';
    const CHANNEL_APP = 'app';
    const CHANNEL_INSTORE = 'instore';

    /**
     * 关联：患者
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * 关联：预约
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * 关联：医生
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * 关联：门店
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * 获取NPS分数
     * 推荐者(9-10) - 贬损者(0-6) = NPS
     */
    public static function calculateNPS($branchId = null, $startDate = null, $endDate = null)
    {
        $query = self::where('status', self::STATUS_COMPLETED);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('survey_date', [$startDate, $endDate]);
        }

        $total = $query->count();
        if ($total == 0) {
            return null;
        }

        $promoters = (clone $query)->where('would_recommend', '>=', 9)->count();
        $detractors = (clone $query)->where('would_recommend', '<=', 6)->count();

        return round((($promoters - $detractors) / $total) * 100);
    }

    /**
     * 获取平均评分
     */
    public static function getAverageRatings($branchId = null, $startDate = null, $endDate = null)
    {
        $query = self::where('status', self::STATUS_COMPLETED);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('survey_date', [$startDate, $endDate]);
        }

        return [
            'overall' => round($query->avg('overall_rating'), 1),
            'service' => round($query->avg('service_rating'), 1),
            'environment' => round($query->avg('environment_rating'), 1),
            'wait_time' => round($query->avg('wait_time_rating'), 1),
            'doctor' => round($query->avg('doctor_rating'), 1),
        ];
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
}
