<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberSharedHolder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'primary_patient_id',
        'shared_patient_id',
        'relationship',
        'is_active',
        '_who_added',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function primaryPatient()
    {
        return $this->belongsTo(Patient::class, 'primary_patient_id');
    }

    public function sharedPatient()
    {
        return $this->belongsTo(Patient::class, 'shared_patient_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
