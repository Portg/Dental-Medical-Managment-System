<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;
    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany('App\User', 'role_id');
    }

    public function rolePermissions()
    {
        return $this->hasMany('App\RolePermission', 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany('App\Permission', 'role_permissions', 'role_id', 'permission_id');
    }

    public function hasPermission($permissionSlug)
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }
}
