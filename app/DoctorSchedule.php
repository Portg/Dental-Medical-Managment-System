<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'shift_id',
        'schedule_date',
        'start_time',
        'end_time',
        'is_recurring',
        'recurring_pattern',
        'recurring_until',
        'recurring_group_id',
        'max_patients',
        'notes',
        'changed_by',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'schedule_date' => 'date:Y-m-d',
        'recurring_until' => 'date:Y-m-d',
        'is_recurring' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('schedule_date', $date);
    }

    public function scopeForMonth($query, $yearMonth)
    {
        return $query->where('schedule_date', 'like', $yearMonth . '%');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Get effective start_time: prefer shift's time, fallback to direct column.
     */
    public function getEffectiveStartTime(): ?string
    {
        if ($this->shift) {
            return $this->shift->start_time;
        }
        return $this->start_time;
    }

    /**
     * Get effective end_time: prefer shift's time, fallback to direct column.
     */
    public function getEffectiveEndTime(): ?string
    {
        if ($this->shift) {
            return $this->shift->end_time;
        }
        return $this->end_time;
    }

    /**
     * Get effective max_patients: prefer shift's value, fallback to direct column.
     */
    public function getEffectiveMaxPatients(): int
    {
        if ($this->shift) {
            return $this->shift->max_patients;
        }
        return $this->max_patients ?? 1;
    }
}
