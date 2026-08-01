<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class Branch extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;
    protected $fillable = ['name', 'is_active', '_who_added'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }
}
