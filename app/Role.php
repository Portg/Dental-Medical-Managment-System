<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'slug', 'hidden_menu_items'];

    protected $casts = [
        'hidden_menu_items' => 'array',
    ];

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

    public function menuItems()
    {
        return $this->belongsToMany('App\Models\MenuItem', 'role_menu_items')
            ->withPivot('url_override');
    }

    public function hasPermission($permissionSlug)
    {
        $slugs = Cache::remember("role:{$this->id}:permissions", 3600, function () {
            return $this->permissions()->pluck('slug')->toArray();
        });

        return in_array($permissionSlug, $slugs);
    }
}
