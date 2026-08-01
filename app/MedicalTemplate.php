<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class MedicalTemplate extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'category', 'type', 'content', 'department',
        'description', 'is_active', 'usage_count', 'created_by', '_who_added'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    /**
     * Scope for system templates
     */
    public function scopeSystem($query)
    {
        return $query->where('category', 'system');
    }

    /**
     * Scope for department templates
     */
    public function scopeDepartment($query, $department = null)
    {
        $query = $query->where('category', 'department');
        if ($department) {
            $query->where('department', $department);
        }
        return $query;
    }

    /**
     * Scope for personal templates belonging to a specific user
     */
    public function scopePersonal($query, $userId)
    {
        return $query->where('category', 'personal')->where('created_by', $userId);
    }

    /**
     * Scope for templates of a specific type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 用户可见的模板：全院模板（system）+ 科室模板（department）+ 自己的个人模板。
     *
     * 原先本作用域有第三个参数 $department，用于把 department 类模板按科室过滤，
     * 但它结构上无从填充：users 表没有科室字段（只有 branch_id），系统里也不存在
     * 用户与科室的映射，因此唯一调用方 MedicalTemplateService::searchTemplates()
     * 从来不传它，该过滤分支恒不执行。留着会让人误以为科室隔离已经生效——尤其在
     * 本查询已放开给所有能进病历书写页的角色之后。故移除该参数，让「department 类
     * 模板对全院可见」这一实际行为显式化。
     *
     * 若将来引入 users.department，科室过滤应加在下面标注的分支上，并同步收紧
     * MedicalTemplateController@search 的权限。
     */
    public function scopeAvailableToUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('category', 'system')
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('category', 'personal')->where('created_by', $userId);
                })
                // 科室模板：目前无用户↔科室映射，对全院可见
                ->orWhere('category', 'department');
        });
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    /**
     * Get content as array
     */
    public function getContentArrayAttribute()
    {
        return json_decode($this->content, true) ?? [];
    }
}
