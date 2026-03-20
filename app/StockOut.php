<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOut extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_REJECTED = 'rejected';

    const OUT_TYPE_REQUISITION = 'requisition';
    const OUT_TYPE_SUPPLIER_RETURN = 'supplier_return';
    const OUT_TYPE_INVENTORY_LOSS = 'inventory_loss';
    const OUT_TYPE_DAMAGE = 'damage';

    protected $fillable = [
        'stock_out_no',
        'out_type',
        'stock_out_date',
        'patient_id',
        'appointment_id',
        'invoice_id',
        'department',
        'recipient',
        'supplier_id',
        'approved_by',
        'approved_at',
        'total_amount',
        'status',
        'stock_insufficient',
        'notes',
        'branch_id',
        '_who_added',
    ];

    protected $casts = [
        'stock_out_date'    => 'date',
        'approved_at'       => 'datetime',
        'total_amount'      => 'decimal:2',
        'stock_insufficient' => 'boolean',
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
     * Get the user who approved this stock out.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if stock out is draft.
     */
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if stock out is confirmed.
     */
    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Check if stock out is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if stock out is pending approval.
     */
    public function isPendingApproval()
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if stock out is rejected.
     */
    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * AG-052: Only draft records can be edited.
     */
    public function canEdit()
    {
        return $this->isDraft();
    }

    /**
     * Only draft records can be submitted for approval.
     */
    public function canSubmit()
    {
        return $this->isDraft();
    }

    /**
     * Only pending_approval records can be approved or rejected.
     */
    public function canApprove()
    {
        return $this->isPendingApproval();
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
        return \App\DictItem::nameByCode('stock_out_type', $this->out_type) ?? $this->out_type;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return \App\DictItem::nameByCode('stock_out_status', $this->status) ?? $this->status;
    }

    public static function outTypeOptions(): array
    {
        return \App\DictItem::listByType('stock_out_type')->pluck('name', 'code')->all();
    }

    public static function statusOptions(): array
    {
        return \App\DictItem::listByType('stock_out_status')->pluck('name', 'code')->all();
    }
}
