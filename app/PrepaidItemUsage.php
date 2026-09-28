<?php

namespace App;

use App\Concerns\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 预收项目的一次核销。
 *
 * 余量不存字段，由「收费行数量 - 核销流水之和」算出来，理由见建表迁移。
 */
class PrepaidItemUsage extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'invoice_item_id', 'patient_id', 'qty', 'used_at',
        'medical_case_id', 'appointment_id', 'doctor_id',
        'notes', '_who_added',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
