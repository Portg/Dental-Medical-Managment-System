<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'category', 'type', 'content', 'department',
        'description', 'is_active', 'usage_count', 'created_by', '_who_added'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    /**
     * Scope for system templates
     */
    public function scopeSystem($query)
    {
        return $query->where('category', 'system');
    }

    /**
     * Scope for department templates
     */
    public function scopeDepartment($query, $department = null)
    {
        $query = $query->where('category', 'department');
        if ($department) {
            $query->where('department', $department);
        }
        return $query;
    }

    /**
     * Scope for personal templates belonging to a specific user
     */
    public function scopePersonal($query, $userId)
    {
        return $query->where('category', 'personal')->where('created_by', $userId);
    }

    /**
     * Scope for templates of a specific type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get templates available to a user (system + department + personal)
     */
    public function scopeAvailableToUser($query, $userId, $department = null)
    {
        return $query->where(function ($q) use ($userId, $department) {
            $q->where('category', 'system')
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('category', 'personal')->where('created_by', $userId);
                })
                ->orWhere(function ($q3) use ($department) {
                    $q3->where('category', 'department');
                    if ($department) {
                        $q3->where('department', $department);
                    }
                });
        });
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    /**
     * Get content as array
     */
    public function getContentArrayAttribute()
    {
        return json_decode($this->content, true) ?? [];
    }
}
