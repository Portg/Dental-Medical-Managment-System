<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\SerializesDatesInAppTimezone;

class SterilizationKitInstrument extends Model
{
    use SerializesDatesInAppTimezone;

    public $timestamps = false;
    protected $fillable = ['kit_id', 'instrument_name', 'quantity', 'sort_order'];

    public function kit()
    {
        return $this->belongsTo(SterilizationKit::class, 'kit_id');
    }
}
