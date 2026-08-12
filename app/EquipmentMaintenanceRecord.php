<?php

namespace App;

use App\Concerns\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentMaintenanceRecord extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'equipment_code', 'equipment_name', 'category', 'location',
        'maintenance_type', 'performed_at', 'next_due_at', 'result', 'vendor',
        'cost', 'operator_id', 'notes',
    ];

    protected $casts = [
        'performed_at' => 'datetime:Y-m-d H:i',
        'next_due_at' => 'date:Y-m-d',
        'cost' => 'decimal:2',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
