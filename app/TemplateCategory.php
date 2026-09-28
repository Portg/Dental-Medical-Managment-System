<?php

namespace App;

use App\Concerns\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 病历模板的学科分类节点（最多三级）。
 *
 * 注意与 medical_templates.category 区分：那一列是模板的归属范围
 * （system / department / personal），本表是学科分类。详见建表迁移。
 */
class TemplateCategory extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    /** 树的最大层数。三级是视频里的形态，也是学科分类的实际需要 */
    public const MAX_DEPTH = 3;

    protected $fillable = ['name', 'parent_id', 'sort_order', 'is_active', '_who_added'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function templates()
    {
        return $this->hasMany(MedicalTemplate::class, 'template_category_id');
    }

    /**
     * 这个节点在树里的层数（根为 1）。
     *
     * 顺着 parent_id 往上数而不是读一个 level 字段：存冗余深度的话，移动节点
     * 时要连着整棵子树改，漏一次树就歪了，而歪了之后没有任何地方会报错。
     *
     * 加圈数上限只是兜底 —— 真出现环说明数据已经坏了，这里不该死循环。
     */
    public function depth(): int
    {
        $depth = 1;
        $node = $this;

        while ($node->parent_id && $depth <= self::MAX_DEPTH + 1) {
            $node = $node->parent;
            if (!$node) {
                break;
            }
            $depth++;
        }

        return $depth;
    }
}
