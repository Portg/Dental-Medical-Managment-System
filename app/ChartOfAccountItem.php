<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class ChartOfAccountItem extends Model
{
    use SerializesDatesInAppTimezone;

    // 表自建表起就有 deleted_at，服务层的裸查询也一直在 whereNull('deleted_at')，
    // 但模型没挂 trait —— 于是 ChartOfAccountCategory::Items() 这类关联读不到过滤，
    // 软删的科目仍会出现在会计科目表页面上。
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'chart_of_account_category_id', '_who_added'];
}
