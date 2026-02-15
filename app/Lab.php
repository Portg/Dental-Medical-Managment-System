<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lab extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'contact', 'phone', 'address',
        'specialties', 'avg_turnaround_days', 'notes',
        'is_active', '_who_added',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function labCases()
    {
        return $this->hasMany('App\LabCase', 'lab_id');
    }
}
