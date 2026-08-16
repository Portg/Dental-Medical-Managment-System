<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class InvoicePayment extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['amount', 'payment_method', 'account_name', 'cheque_no', 'bank_name',
        'transaction_ref', 'payment_date', 'invoice_id', 'insurance_company_id', 'self_account_id', 'branch_id',
        // 本笔发过累计消费/积分没有 —— 撤销时据此决定要不要冲减，历史收款为 false
        'member_benefits_awarded', '_who_added'];

    protected $casts = [
        'member_benefits_awarded' => 'boolean',
    ];

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

}
