<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'work_status',
        'color',
        'sort_order',
        'max_patients',
        '_who_added',
    ];

    const STATUS_ON_DUTY = 'on_duty';
    const STATUS_REST = 'rest';

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function scopeOnDuty($query)
    {
        return $query->where('work_status', self::STATUS_ON_DUTY);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getTimeRangeAttribute(): string
    {
        return substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5);
    }

    public function isOnDuty(): bool
    {
        return $this->work_status === self::STATUS_ON_DUTY;
    }
}
