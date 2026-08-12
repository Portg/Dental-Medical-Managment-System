<?php

namespace App;

use App\Concerns\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalWasteHandoverRecord extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'waste_type', 'weight_kg', 'package_count', 'handed_over_at',
        'handler_id', 'receiver_name', 'carrier', 'manifest_no', 'destination', 'notes',
    ];

    protected $casts = [
        'handed_over_at' => 'datetime:Y-m-d H:i',
        'weight_kg' => 'decimal:2',
    ];

    public function handler()
    {
        return $this->belongsTo(User::class, 'handler_id');
    }
}
