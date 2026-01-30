<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentPlanItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'treatment_plan_id',
        'stage_id',
        'item_name',
        'quantity',
        'unit_price',
        'subtotal',
        'related_teeth',
        'material_details',
        'estimated_duration_minutes',
        'sequence',
        'status',
        '_who_added',
    ];

    protected $casts = [
        'related_teeth' => 'array',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->subtotal = $model->quantity * $model->unit_price;
        });
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function stage()
    {
        return $this->belongsTo(TreatmentPlanStage::class, 'stage_id');
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
