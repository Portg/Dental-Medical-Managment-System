<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountCategory extends Model
{
    protected $fillable = ['name', 'description', 'accounting_equation_id', '_who_added'];

    public function accountingEquation()
    {
        return $this->belongsTo('App\AccountingEquation', 'accounting_equation_id');
    }

    public function Items()
    {
        return $this->hasMany('App\ChartOfAccountItem','chart_of_account_category_id');
    }
}
