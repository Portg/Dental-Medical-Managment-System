<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ExceptionLog extends Model
{
    protected $fillable = [
        'exception_type',
        'message',
        'stack_trace',
        'user_id',
        'request_url',
        'request_method',
        'request_data',
        'response_status',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'request_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeForType($query, $exceptionType)
    {
        return $query->where('exception_type', $exceptionType);
    }

    public static function logException(\Throwable $exception, $responseStatus = 500)
    {
        $request = request();
        $requestData = $request->except(['password', 'password_confirmation', 'token']);

        return static::create([
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
            'user_id' => Auth::id(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'request_data' => $requestData,
            'response_status' => $responseStatus,
            'occurred_at' => now(),
        ]);
    }

    public static function cleanupOldLogs($days = 180)
    {
        return static::where('occurred_at', '<', now()->subDays($days))->delete();
    }
}
