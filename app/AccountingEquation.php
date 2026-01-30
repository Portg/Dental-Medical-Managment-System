<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountingEquation extends Model
{
    public function categories()
    {
        return $this->hasMany(ChartOfAccountCategory::class, 'accounting_equation_id');
    }
}