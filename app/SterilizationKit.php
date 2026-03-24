<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SterilizationKit extends Model
{
    use SoftDeletes;

    protected $fillable = ['kit_no', 'name', 'is_active', '_who_added'];

    protected $casts = ['is_active' => 'boolean'];

    public function instruments()
    {
        return $this->hasMany(SterilizationKitInstrument::class, 'kit_id')->orderBy('sort_order');
    }

    public function records()
    {
        return $this->hasMany(SterilizationRecord::class, 'kit_id');
    }
}
