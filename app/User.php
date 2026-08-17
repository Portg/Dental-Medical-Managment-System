<?php

namespace App;

use App\Traits\EncryptsNin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Concerns\SerializesDatesInAppTimezone;

class User extends Authenticatable
{
    use SerializesDatesInAppTimezone;
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
        'email_verified_at' => 'datetime:Y-m-d H:i',
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
     * 在职医生的 [{id, name}] 列表，供划价面板的「操作医生」下拉用。
     *
     * 患者页和诊疗页共用同一个划价面板，两边都要这份列表；抽出来是为了别在两个
     * Service 里各写一遍查询 —— 一边加了 status 过滤另一边没加，下拉里就会出现
     * 离职医生，而提成是按这个 id 记的。
     */
    public static function activeDoctorOptions(): \Illuminate\Support\Collection
    {
        return self::where('is_doctor', true)
            ->whereNull('deleted_at')
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('surname')
            ->get(['id', 'surname', 'othername'])
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->full_name])
            ->values();
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
