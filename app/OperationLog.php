<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OperationLog extends Model
{
    protected $fillable = [
        'user_id',
        'operation_type',
        'module',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'change_reason',
        'operation_time',
        'ip_address',
    ];

    protected $casts = [
        'operation_time' => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeForResource($query, $resourceType, $resourceId = null)
    {
        $query->where('resource_type', $resourceType);
        if ($resourceId) {
            $query->where('resource_id', $resourceId);
        }
        return $query;
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('operation_time', '>=', now()->subDays($days));
    }

    public static function log($operationType, $module, $resourceType, $resourceId = null, $oldValues = null, $newValues = null, $changeReason = null)
    {
        return static::create([
            'user_id' => Auth::id(),
            'operation_type' => $operationType,
            'module' => $module,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'change_reason' => $changeReason,
            'operation_time' => now(),
            'ip_address' => request()->ip(),
        ]);
    }

    public static function logCreate($module, $resourceType, $resourceId, $newValues)
    {
        return static::log('create', $module, $resourceType, $resourceId, null, $newValues);
    }

    public static function logUpdate($module, $resourceType, $resourceId, $oldValues, $newValues, $changeReason = null)
    {
        return static::log('update', $module, $resourceType, $resourceId, $oldValues, $newValues, $changeReason);
    }

    public static function logDelete($module, $resourceType, $resourceId, $oldValues)
    {
        return static::log('delete', $module, $resourceType, $resourceId, $oldValues, null);
    }

    /**
     * Check if current user is exporting too frequently.
     * Logs a warning if threshold is exceeded.
     */
    public static function checkExportFrequency(): void
    {
        $threshold = config('data_security.export_alert.threshold', 5);
        $windowMinutes = config('data_security.export_alert.window_minutes', 60);

        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $recentCount = static::where('user_id', $userId)
            ->where('operation_type', 'export')
            ->where('operation_time', '>=', now()->subMinutes($windowMinutes))
            ->count();

        if ($recentCount >= $threshold) {
            \Illuminate\Support\Facades\Log::warning(
                "频繁导出告警: 用户 {$userId} 在 {$windowMinutes} 分钟内导出 {$recentCount} 次"
            );
        }
    }
}
