<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabCaseItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lab_case_id', 'prosthesis_type', 'material',
        'color_shade', 'teeth_positions', 'qty', 'sort_order',
    ];

    protected $casts = [
        'teeth_positions' => 'array',
    ];

    public function labCase()
    {
        return $this->belongsTo(LabCase::class, 'lab_case_id');
    }
}
