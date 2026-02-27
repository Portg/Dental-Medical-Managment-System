<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MemberAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        '_who_added',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, '_who_added');
    }

    /**
     * Record a single field change.
     */
    public static function log(int $patientId, string $action, ?string $field = null, $oldValue = null, $newValue = null): self
    {
        return self::create([
            'patient_id' => $patientId,
            'action'     => $action,
            'field_name' => $field,
            'old_value'  => is_null($oldValue) ? null : (string) $oldValue,
            'new_value'  => is_null($newValue) ? null : (string) $newValue,
            '_who_added' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    /**
     * Record multiple field changes at once.
     */
    public static function logChanges(int $patientId, string $action, array $original, array $changes): void
    {
        foreach ($changes as $field => $newValue) {
            $oldValue = $original[$field] ?? null;
            if ((string) $oldValue !== (string) $newValue) {
                self::log($patientId, $action, $field, $oldValue, $newValue);
            }
        }
    }
}
