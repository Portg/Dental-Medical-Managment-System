<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientTag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'color', 'icon', 'description', 'sort_order', 'is_active', '_who_added'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    /**
     * Get all patients with this tag
     */
    public function patients()
    {
        return $this->belongsToMany('App\Patient', 'patient_tag_pivot', 'tag_id', 'patient_id');
    }

    /**
     * Scope for active tags
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered tags
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}
