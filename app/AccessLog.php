<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AccessLog extends Model
{
    protected $fillable = [
        'user_id',
        'accessed_resource',
        'resource_type',
        'resource_id',
        'ip_address',
        'access_time',
    ];

    protected $casts = [
        'access_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
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
        return $query->where('access_time', '>=', now()->subDays($days));
    }

    public static function log($accessedResource, $resourceType, $resourceId = null)
    {
        return static::create([
            'user_id' => Auth::id(),
            'accessed_resource' => $accessedResource,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'access_time' => now(),
        ]);
    }

    public static function logSensitiveAccess($resourceType, $resourceId, $fieldAccessed)
    {
        return static::log(
            "Accessed sensitive field: {$fieldAccessed}",
            $resourceType,
            $resourceId
        );
    }
}
