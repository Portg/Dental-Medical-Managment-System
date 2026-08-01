<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SatisfactionSurvey extends Model
{
    use SoftDeletes;

    protected $table = 'satisfaction_surveys';

    protected $fillable = [
        'token',
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
        'sent_at',
        'expires_at',
        'is_anonymous',
        'status'
    ];

    protected $casts = [
        'survey_date' => 'datetime:Y-m-d H:i',
        'sent_at'     => 'datetime:Y-m-d H:i',
        'expires_at'  => 'datetime:Y-m-d H:i',
    ];

    /**
     * token 只用于服务端换取问卷，不应出现在任何 API/视图输出里。
     */
    protected $hidden = ['token'];

    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED = 'expired';

    // 渠道常量
    const CHANNEL_SMS = 'sms';
    const CHANNEL_WECHAT = 'wechat';
    const CHANNEL_APP = 'app';
    const CHANNEL_INSTORE = 'instore';

    /** 填写链接默认有效天数 */
    const DEFAULT_VALID_DAYS = 30;

    /**
     * 生成一个全局唯一的填写凭证。
     *
     * 用 random_bytes 而非 Str::random —— 该 token 是患者侧唯一的身份凭证，
     * 猜中即可读取并覆盖他人的问卷，必须用密码学安全的随机源。
     */
    public static function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16)); // 32 位十六进制
        } while (self::withTrashed()->where('token', $token)->exists());

        return $token;
    }

    /**
     * 链接是否已过期（未设置 expires_at 视为长期有效）。
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * 是否还能被患者填写。
     */
    public function canBeFilled(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    /**
     * 公开填写链接（发给患者的完整 URL）。
     */
    public function getFillUrlAttribute(): ?string
    {
        return $this->token ? url('/survey/' . $this->token) : null;
    }

    /**
     * 作用域：待填写且未过期。
     */
    public function scopeOpenForFilling($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

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
