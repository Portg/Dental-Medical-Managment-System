<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SterilizationKitInstrument extends Model
{
    public $timestamps = false;
    protected $fillable = ['kit_id', 'instrument_name', 'quantity', 'sort_order'];

    public function kit()
    {
        return $this->belongsTo(SterilizationKit::class, 'kit_id');
    }
}
