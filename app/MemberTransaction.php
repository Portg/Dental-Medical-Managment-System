<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_no',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'points_change',
        'payment_method',
        'description',
        'patient_id',
        'invoice_id',
        '_who_added',
    ];

    /**
     * Generate unique transaction number
     */
    public static function generateTransactionNo()
    {
        $year = date('Y');
        $month = date('m');
        $lastTransaction = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->transaction_no, -5));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'MT' . $year . $month . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the patient.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the invoice if related.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who added the transaction.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Scope for deposits.
     */
    public function scopeDeposits($query)
    {
        return $query->where('transaction_type', 'Deposit');
    }

    /**
     * Scope for consumption.
     */
    public function scopeConsumption($query)
    {
        return $query->where('transaction_type', 'Consumption');
    }
}
