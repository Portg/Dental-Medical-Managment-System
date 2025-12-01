<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get all roles that have this permission
     */
    public function roles()
    {
        return $this->belongsToMany('App\Role', 'role_permissions', 'permission_id', 'role_id')
                    ->withTimestamps();
    }
}
