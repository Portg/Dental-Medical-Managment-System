<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class SalaryScale extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['employee_id', 'amount', '_who_added'];

    public function AddedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    public function employee()
    {
        return $this->belongsTo('App\User', 'employee_id');
    }
}
