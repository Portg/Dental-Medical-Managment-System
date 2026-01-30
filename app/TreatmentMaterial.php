<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'medical_case_id',
        'appointment_id',
        'patient_id',
        'inventory_item_id',
        'material_name',
        'material_code',
        'related_tooth_number',
        'dental_chart_id',
        'material_type',
        'quantity_used',
        'cost_per_unit',
        'total_cost',
        'supplier_id',
        'notes',
        '_who_added',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_cost = $model->quantity_used * $model->cost_per_unit;
        });
    }

    public function medicalCase()
    {
        return $this->belongsTo(MedicalCase::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function dentalChart()
    {
        return $this->belongsTo(DentalChart::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function whoAdded()
    {
        return $this->belongsTo(User::class, '_who_added');
    }
}
