<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrescriptionItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'prescription_id',
        'drug_name',
        'dosage',
        'quantity',
        'frequency',
        'duration',
        'usage',
        'notes',
        'inventory_item_id',
        '_who_added',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }
}
