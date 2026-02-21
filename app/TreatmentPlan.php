<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class TreatmentPlan extends Model implements AuditableContract
{
    use SoftDeletes, Auditable;

    protected $auditExclude = ['updated_at', 'created_at', 'final_price'];

    public function generateTags(): array
    {
        return ['treatment'];
    }

    const STATUS_PLANNED = 'Planned';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_CANCELLED = 'Cancelled';

    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';
    const APPROVAL_REVISION_NEEDED = 'revision_needed';

    protected $fillable = [
        'plan_name', 'description', 'planned_procedures',
        'related_teeth', 'estimated_cost', 'actual_cost',
        'total_price', 'discount_rate', 'final_price',
        'status', 'approval_status', 'priority',
        'confirmed_by', 'confirmed_at', 'electronic_signature', 'risk_disclosure',
        'start_date', 'target_completion_date', 'actual_completion_date',
        'completion_notes', 'medical_case_id', 'patient_id', '_who_added'
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'confirmed_at' => 'datetime',
        'related_teeth' => 'array',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-calculate final price
            if ($model->total_price > 0 && $model->discount_rate > 0) {
                $model->final_price = $model->total_price * (1 - $model->discount_rate / 100);
            } elseif ($model->total_price > 0) {
                $model->final_price = $model->total_price;
            }
        });
    }

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Patient', 'patient_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function confirmedBy()
    {
        return $this->belongsTo('App\User', 'confirmed_by');
    }

    public function stages()
    {
        return $this->hasMany('App\TreatmentPlanStage', 'treatment_plan_id')->orderBy('stage_number');
    }

    public function items()
    {
        return $this->hasMany('App\TreatmentPlanItem', 'treatment_plan_id')->orderBy('sequence');
    }

    /**
     * Scope for draft plans
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for confirmed plans
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope for in-progress plans
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope for completed plans
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute()
    {
        $totalItems = $this->items()->count();
        if ($totalItems === 0) {
            return 0;
        }
        $completedItems = $this->items()->where('status', 'completed')->count();
        return round(($completedItems / $totalItems) * 100);
    }

    /**
     * Confirm the plan
     */
    public function confirm($userId, $signature = null)
    {
        $this->status = 'confirmed';
        $this->confirmed_by = $userId;
        $this->confirmed_at = now();
        if ($signature) {
            $this->electronic_signature = $signature;
        }
        $this->save();
        return $this;
    }

    /**
     * Calculate total from items
     */
    public function calculateTotal()
    {
        $this->total_price = $this->items()->sum('subtotal');
        $this->save();
        return $this;
    }
}
