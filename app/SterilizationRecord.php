<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SterilizationRecord extends Model
{
    use SoftDeletes;

    const STATUS_VALID  = 'valid';
    const STATUS_USED   = 'used';
    const STATUS_VOIDED = 'voided';

    const METHOD_AUTOCLAVE = 'autoclave';
    const METHOD_CHEMICAL  = 'chemical';
    const METHOD_DRY_HEAT  = 'dry_heat';

    // 有效期天数（按灭菌方式）
    const EXPIRY_DAYS = [
        self::METHOD_AUTOCLAVE => 90,
        self::METHOD_DRY_HEAT  => 90,
        self::METHOD_CHEMICAL  => 30,
    ];

    protected $fillable = [
        'kit_id', 'batch_no', 'method', 'temperature', 'duration_minutes',
        'operator_id', 'sterilized_at', 'expires_at', 'status', 'notes',
    ];

    protected $casts = [
        'sterilized_at' => 'datetime',
        'expires_at'    => 'datetime',
    ];

    /**
     * 实时判断是否已过期（不依赖 status 字段）
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->status === self::STATUS_VALID;
    }

    public function kit()
    {
        return $this->belongsTo(SterilizationKit::class, 'kit_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function usage()
    {
        return $this->hasOne(SterilizationUsage::class, 'record_id')->whereNull('deleted_at');
    }
}
