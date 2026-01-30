<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    protected $fillable = ['role_id', 'permission_id'];

    public function role(): BelongsTo
    {
        return $this->belongsTo('App\Role', 'role_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo('App\Permission', 'permission_id');
    }
}
