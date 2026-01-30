<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chair extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chair_code',
        'chair_name',
        'status',
        'branch_id',
        'notes',
        '_who_added',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
