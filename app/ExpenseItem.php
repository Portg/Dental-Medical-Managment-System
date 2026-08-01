<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class ExpenseItem extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['expense_category_id','description', 'qty', 'price', 'expense_id', '_who_added'];

    public function AddedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
