<?php

namespace App;

use App\Traits\EncryptsNin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use EncryptsNin, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_RESIGNED = 'resigned';

    protected $fillable = [
        'surname', 'othername', 'username', 'email', 'phone_no', 'alternative_phone_no', 'photo', 'nin', 'role_id',
        'branch_id', 'is_doctor', 'password', 'status',
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_doctor' => 'boolean',
    ];

    /**
     * Accessor: join name based on locale
     */
    public function getFullNameAttribute()
    {
        if (app()->getLocale() === 'zh-CN') {
            return $this->surname . $this->othername;
        }
        return $this->surname . ' ' . $this->othername;
    }

    public function UserRole()
    {
        return $this->belongsTo('App\Role', 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo('App\Branch', 'branch_id');
    }

    public function hasPermission($permissionSlug)
    {
        if (!$this->UserRole) {
            return false;
        }
        return $this->UserRole->hasPermission($permissionSlug);
    }

    public function permissions()
    {
        if (!$this->UserRole) {
            return collect([]);
        }
        return $this->UserRole->permissions;
    }

    /**
     * Scope: only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Mark user as resigned (AG-027: clear all tokens).
     */
    public function markAsResigned(): void
    {
        $this->update(['status' => self::STATUS_RESIGNED]);
        $this->tokens()->delete();
    }

    /**
     * Reactivate user (AG-031: must reset password externally).
     */
    public function markAsActive(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }
}
