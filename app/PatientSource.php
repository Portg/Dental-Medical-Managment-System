<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientSource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'is_active', '_who_added'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    /**
     * Get all patients from this source
     */
    public function patients()
    {
        return $this->hasMany('App\Patient', 'source_id');
    }

    /**
     * Scope for active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
