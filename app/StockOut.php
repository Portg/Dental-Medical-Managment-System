<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOut extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stock_out_no',
        'out_type',
        'stock_out_date',
        'patient_id',
        'appointment_id',
        'department',
        'total_amount',
        'status',
        'notes',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'stock_out_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the patient (for treatment type).
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the appointment (for treatment type).
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the items for this stock out.
     */
    public function items()
    {
        return $this->hasMany(StockOutItem::class, 'stock_out_id');
    }

    /**
     * Get the user who added this stock out.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Check if stock out is draft.
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if stock out is confirmed.
     */
    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if stock out is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Generate stock out number.
     */
    public static function generateStockOutNo()
    {
        $prefix = 'SO';
        $date = date('Ymd');
        $lastRecord = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRecord ? intval(substr($lastRecord->stock_out_no, -4)) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate and update total amount.
     */
    public function updateTotalAmount()
    {
        $this->total_amount = $this->items()->sum('amount');
        $this->save();
    }

    /**
     * Get out type label.
     */
    public function getOutTypeLabelAttribute()
    {
        $types = [
            'treatment' => __('inventory.out_type_treatment'),
            'department' => __('inventory.out_type_department'),
            'damage' => __('inventory.out_type_damage'),
            'other' => __('inventory.out_type_other'),
        ];

        return $types[$this->out_type] ?? $this->out_type;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'draft' => __('inventory.status_draft'),
            'confirmed' => __('inventory.status_confirmed'),
            'cancelled' => __('inventory.status_cancelled'),
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
