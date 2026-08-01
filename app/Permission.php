<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Concerns\SerializesDatesInAppTimezone;

class Permission extends Model
{
    use SerializesDatesInAppTimezone;

    protected $fillable = ['name', 'slug', 'description', 'module'];

    public function rolePermissions()
    {
        return $this->hasMany('App\RolePermission', 'permission_id');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role', 'role_permissions', 'permission_id', 'role_id');
    }

}
