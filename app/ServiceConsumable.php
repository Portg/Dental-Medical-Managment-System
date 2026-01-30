<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceConsumable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'medical_service_id',
        'inventory_item_id',
        'qty',
        'is_required',
        '_who_added',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    /**
     * Get the medical service.
     */
    public function medicalService()
    {
        return $this->belongsTo(MedicalService::class, 'medical_service_id');
    }

    /**
     * Get the inventory item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the user who added this consumable.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Scope to get required consumables.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to get optional consumables.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}
