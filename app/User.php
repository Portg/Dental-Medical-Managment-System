<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'surname', 'othername', 'email', 'phone_no', 'alternative_phone_no', 'photo', 'nin', 'role_id',
        'branch_id', 'is_doctor', 'password',
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
    ];

    public function UserRole()
    {
        return $this->belongsTo('App\Role', 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo('App\Branch', 'branch_id');
    }

    /**
     * Check if user has a specific permission through their role
     */
    public function hasPermission($permission)
    {
        if (!$this->UserRole) {
            return false;
        }

        return $this->UserRole->hasPermission($permission);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(...$permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(...$permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        if (!$this->UserRole) {
            return false;
        }

        if (is_string($role)) {
            return $this->UserRole->name === $role;
        }

        return $this->role_id === $role->id;
    }

    /**
     * Check if user is a super administrator
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('Super Administrator');
    }

    /**
     * Check if user is an administrator
     */
    public function isAdmin()
    {
        return $this->hasRole('Administrator');
    }

    /**
     * Check if user is a doctor
     */
    public function isDoctor()
    {
        return $this->hasRole('Doctor');
    }

    /**
     * Check if user is a receptionist
     */
    public function isReceptionist()
    {
        return $this->hasRole('Receptionist');
    }

    /**
     * Check if user is a nurse
     */
    public function isNurse()
    {
        return $this->hasRole('Nurse');
    }
}
