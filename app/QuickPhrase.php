<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuickPhrase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shortcut', 'phrase', 'category', 'scope', 'is_active', 'user_id', '_who_added'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    /**
     * Scope for active phrases
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system phrases
     */
    public function scopeSystem($query)
    {
        return $query->where('scope', 'system');
    }

    /**
     * Scope for personal phrases belonging to a specific user
     */
    public function scopePersonal($query, $userId)
    {
        return $query->where('scope', 'personal')->where('user_id', $userId);
    }

    /**
     * Get phrases available to a user (system + personal)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('scope', 'system')
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('scope', 'personal')->where('user_id', $userId);
                });
        });
    }

    /**
     * Scope for phrases by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Search phrases by shortcut or phrase content
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('shortcut', 'like', "%{$term}%")
                ->orWhere('phrase', 'like', "%{$term}%");
        });
    }
}
