<?php

namespace App;

use App\Concerns\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicDisinfectionRecord extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'area', 'check_type', 'disinfectant', 'concentration',
        'performed_at', 'result', 'corrective_action', 'operator_id',
        'reviewer_id', 'reviewed_at', 'notes',
    ];

    protected $casts = [
        'performed_at' => 'datetime:Y-m-d H:i',
        'reviewed_at' => 'datetime:Y-m-d H:i',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
