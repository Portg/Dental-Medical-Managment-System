<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrescriptionItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'prescription_id',
        'medical_service_id',
        'drug_name',
        'dosage',
        'quantity',
        'unit_price',
        'frequency',
        'duration',
        'usage',
        'notes',
        'inventory_item_id',
        '_who_added',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicalService()
    {
        return $this->belongsTo(MedicalService::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * 行金额 = 单价 × 数量
     */
    public function getAmountAttribute(): string
    {
        return bcmul($this->unit_price ?? '0', (string) $this->quantity, 2);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }
}
