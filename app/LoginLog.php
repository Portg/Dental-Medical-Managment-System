<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'login_time',
        'ip_address',
        'device_info',
        'login_status',
        'failure_reason',
        'session_id',
        'logout_time',
    ];

    protected $casts = [
        'login_time' => 'datetime',
        'logout_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('login_status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('login_status', 'failed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('login_time', '>=', now()->subDays($days));
    }

    public static function logLogin($userId, $username, $ipAddress, $deviceInfo, $status, $failureReason = null)
    {
        return static::create([
            'user_id' => $userId,
            'username' => $username,
            'login_time' => now(),
            'ip_address' => $ipAddress,
            'device_info' => $deviceInfo,
            'login_status' => $status,
            'failure_reason' => $failureReason,
            'session_id' => session()->getId(),
        ]);
    }

    public static function logLogout($userId)
    {
        $lastLogin = static::where('user_id', $userId)
            ->whereNull('logout_time')
            ->latest('login_time')
            ->first();

        if ($lastLogin) {
            $lastLogin->update(['logout_time' => now()]);
        }

        return $lastLogin;
    }
}
