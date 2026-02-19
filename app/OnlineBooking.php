<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OnlineBooking extends Model
{
    const STATUS_WAITING = 'Waiting';
    const STATUS_ACCEPTED = 'Accepted';
    const STATUS_REJECTED = 'Rejected';

    protected $fillable = ['full_name', 'email', 'phone_no', 'start_date', 'end_date', 'start_time', 'message', 'visit_history', 'insurance_company_id', 'status'];

    protected $casts = [
        'visit_history' => 'boolean',
    ];
}
