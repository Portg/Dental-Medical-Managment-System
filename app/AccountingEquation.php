<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingEquation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sort_by',
        'active_tab',
        '_who_added',
    ];

    protected $casts = [
        'active_tab' => 'boolean',
    ];

    public function categories()
    {
        return $this->hasMany(ChartOfAccountCategory::class, 'accounting_equation_id');
    }
}