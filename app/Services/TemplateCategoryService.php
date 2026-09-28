<?php

namespace App\Services;

use App\MedicalTemplate;
use App\TemplateCategory;
use Illuminate\Support\Facades\DB;

/**
 * 病历模板的学科分类树（最多三级）。
 *
 * 与 medical_templates.category（归属范围）无关，见 TemplateCategory 的说明。
 */
class TemplateCategoryService
{
    /**
     * 整棵树，带每个节点下的模板数。
     *
     * 一次查全部再在内存里组装，而不是递归查库：分类是个位到几十的量级，
     * 递归查会变成 N 次查询换零收益。计数用一次分组查询顺带算出来。
     */
    public function tree(bool $onlyActive = false): array
    {
        $query = TemplateCategory::query()->orderBy('sort_order')->orderBy('id');
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        $nodes = $query->get();

        $counts = DB::table('medical_templates')
            ->whereNull('deleted_at')
            ->whereNotNull('template_category_id')
            ->groupBy('template_category_id')
            ->select('template_category_id', DB::raw('COUNT(*) as n'))
            ->pluck('n', 'template_category_id');

        $byParent = [];
        foreach ($nodes as $node) {
            $byParent[$node->parent_id ?? 0][] = $node;
        }

        return $this->buildBranch($byParent, 0, $counts);
    }

    /**
     * @param array $byParent parent_id => 子节点数组
     */
    private function buildBranch(array $byParent, int $parentId, $counts): array
    {
        $branch = [];

        foreach ($byParent[$parentId] ?? [] as $node) {
            $children = $this->buildBranch($byParent, $node->id, $counts);

            $branch[] = [
                'id'             => $node->id,
                'name'           => $node->name,
                'parent_id'      => $node->parent_id,
                'sort_order'     => $node->sort_order,
                'is_active'      => (bool) $node->is_active,
                'template_count' => (int) ($counts[$node->id] ?? 0),
                // 子树里的模板总数：前端折叠着父节点时也要能看出下面有没有东西
                'total_count'    => (int) ($counts[$node->id] ?? 0)
                    + array_sum(array_column($children, 'total_count')),
                'children'       => $children,
            ];
        }

        return $branch;
    }

    /**
     * 新建一个分类节点。
     *
     * 三级上限在这里挡：树深了之后界面就没法在一屏里展开，而学科分类本身
     * 也不需要更深 —— 视频里就是一级/二级/三级三档。
     */
    public function create(array $data, int $userId): array
    {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId) {
            $parent = TemplateCategory::find($parentId);
            if (!$parent) {
                return ['success' => false, 'message' => __('templates.category_parent_missing')];
            }
            if ($parent->depth() >= TemplateCategory::MAX_DEPTH) {
                return [
                    'success' => false,
                    'message' => __('templates.category_too_deep', ['max' => TemplateCategory::MAX_DEPTH]),
                ];
            }
        }

        $category = TemplateCategory::create([
            'name'       => $data['name'],
            'parent_id'  => $parentId ?: null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => $data['is_active'] ?? true,
            '_who_added' => $userId,
        ]);

        return ['success' => true, 'id' => $category->id];
    }

    /**
     * 改名 / 挪位置 / 停用。
     *
     * 换父节点时要挡两件事：不能挂到自己的子树下（会把子树从树上切下来，
     * 变成一个谁也看不见的环），以及挪过去之后不能超过三级。
     */
    public function update(int $id, array $data, int $userId): array
    {
        $category = TemplateCategory::find($id);
        if (!$category) {
            return ['success' => false, 'message' => __('templates.category_not_found')];
        }

        if (array_key_exists('parent_id', $data)) {
            $newParentId = $data['parent_id'] ?: null;

            if ($newParentId) {
                if ((int) $newParentId === $id) {
                    return ['success' => false, 'message' => __('templates.category_cycle')];
                }

                $parent = TemplateCategory::find($newParentId);
                if (!$parent) {
                    return ['success' => false, 'message' => __('templates.category_parent_missing')];
                }
                if ($this->isDescendantOf($parent, $id)) {
                    return ['success' => false, 'message' => __('templates.category_cycle')];
                }
                if ($parent->depth() + $this->subtreeHeight($category) > TemplateCategory::MAX_DEPTH) {
                    return [
                        'success' => false,
                        'message' => __('templates.category_too_deep', ['max' => TemplateCategory::MAX_DEPTH]),
                    ];
                }
            }

            $category->parent_id = $newParentId;
        }

        foreach (['name', 'sort_order', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $category->{$field} = $data[$field];
            }
        }

        $category->save();

        return ['success' => true];
    }

    /**
     * 删除一个分类。
     *
     * 下面还挂着子分类或模板时不让删：级联删会把模板一起带走，而模板是
     * 医生一条条写出来的；给个明确的拒绝，让人先把东西挪走。
     */
    public function delete(int $id): array
    {
        $category = TemplateCategory::find($id);
        if (!$category) {
            return ['success' => false, 'message' => __('templates.category_not_found')];
        }

        if ($category->children()->exists()) {
            return ['success' => false, 'message' => __('templates.category_has_children')];
        }

        if (MedicalTemplate::where('template_category_id', $id)->exists()) {
            return ['success' => false, 'message' => __('templates.category_has_templates')];
        }

        $category->delete();

        return ['success' => true];
    }

    /** $node 是不是 $ancestorId 的后代（含自身） */
    private function isDescendantOf(TemplateCategory $node, int $ancestorId): bool
    {
        $current = $node;
        $guard = 0;

        while ($current && $guard++ <= TemplateCategory::MAX_DEPTH + 1) {
            if ($current->id === $ancestorId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /** 以 $node 为根的子树有几层（自身算 1 层） */
    private function subtreeHeight(TemplateCategory $node): int
    {
        $children = $node->children;
        if ($children->isEmpty()) {
            return 1;
        }

        $max = 0;
        foreach ($children as $child) {
            $max = max($max, $this->subtreeHeight($child));
        }

        return $max + 1;
    }
}
