<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\SerializesDatesInAppTimezone;

class ChartOfAccountItem extends Model
{
    use SerializesDatesInAppTimezone;

    protected $fillable = ['name', 'description', 'chart_of_account_category_id', '_who_added'];
}
