<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class leaveRequest extends Model
{
    use  SoftDeletes;

    const STATUS_PENDING_APPROVAL = 'Pending Approval';
    const STATUS_APPROVED = 'Approved';
    const STATUS_REJECTED = 'Rejected';

    protected  $fillable=['leave_type_id','start_date','duration','status','action_date','_who_added','_approved_by'];
}
