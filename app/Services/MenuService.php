<?php

namespace App\Services;

use App\Models\MenuItem;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    /**
     * 获取用户的菜单树（权限驱动：自动根据用户权限过滤可见菜单）。
     */
    public function getMenuTreeForUser(User $user): Collection
    {
        // 缓存完整菜单树（所有活跃项），各用户共享同一份
        $tree = Cache::remember('menu_tree:all', 3600, function () {
            return $this->buildFullTree();
        });

        // 运行时按用户权限过滤
        $filtered = $this->filterByPermission($tree, $user);

        // 应用角色级隐藏覆盖
        $role = $user->UserRole;
        $hiddenIds = $role ? ($role->hidden_menu_items ?? []) : [];
        if (!empty($hiddenIds)) {
            $filtered = $this->filterHidden($filtered, $hiddenIds);
        }

        return $filtered;
    }

    /**
     * 清除菜单缓存。
     */
    public function clearAllCache(): void
    {
        Cache::forget('menu_tree:all');
    }

    /**
     * 构建完整菜单树（所有活跃菜单项）。
     */
    private function buildFullTree(): Collection
    {
        $items = MenuItem::where('is_active', true)
            ->with('permission')
            ->orderBy('sort_order')
            ->get();

        foreach ($items as $item) {
            $item->effective_url = $item->url;
        }

        return $this->nestItems($items);
    }

    /**
     * 将扁平列表转为嵌套树。
     */
    private function nestItems(Collection $items): Collection
    {
        $grouped = $items->groupBy('parent_id');
        $roots = $grouped->get('', collect());

        // parent_id 为 NULL 的也算根节点
        if ($grouped->has(null)) {
            $roots = $roots->merge($grouped->get(null));
        }

        $roots->each(function ($item) use ($grouped) {
            $this->assignChildren($item, $grouped);
        });

        return $roots->values();
    }

    /**
     * 递归挂载子节点。
     */
    private function assignChildren($item, Collection $grouped): void
    {
        $children = $grouped->get($item->id, collect());
        $item->setRelation('children', $children);
        $children->each(fn ($child) => $this->assignChildren($child, $grouped));
    }

    /**
     * 获取角色的侧边栏预览数据（含所有权限可见项 + 隐藏状态标记）。
     */
    public function getPreviewTreeForRole(\App\Role $role): array
    {
        $tree = Cache::remember('menu_tree:all', 3600, function () {
            return $this->buildFullTree();
        });

        $permissionSlugs = $role->permissions()->pluck('slug')->toArray();
        $hiddenIds = $role->hidden_menu_items ?? [];

        return $this->buildPreviewArray($tree, $permissionSlugs, $hiddenIds);
    }

    /**
     * 递归构建预览数组：标记每项的 has_permission 和 is_hidden 状态。
     */
    private function buildPreviewArray(Collection $tree, array $permSlugs, array $hiddenIds): array
    {
        $result = [];
        foreach ($tree as $item) {
            $hasPerm = true;
            if ($item->permission_id && $item->permission) {
                $hasPerm = in_array($item->permission->slug, $permSlugs);
            }

            $children = [];
            if ($item->children->isNotEmpty()) {
                $children = $this->buildPreviewArray($item->children, $permSlugs, $hiddenIds);
            }

            // 目录节点：至少有一个有权限的子项才显示
            $hasVisibleChild = collect($children)->contains(fn ($c) => $c['has_permission']);
            if (!$item->url && empty($children)) {
                continue;
            }
            if (!$item->url && !$hasVisibleChild && !$hasPerm) {
                continue;
            }

            $result[] = [
                'id'             => $item->id,
                'title'          => __($item->title_key),
                'icon'           => $item->icon,
                'url'            => $item->url,
                'has_permission' => $hasPerm,
                'is_hidden'      => in_array($item->id, $hiddenIds),
                'children'       => $children,
            ];
        }
        return $result;
    }

    /**
     * 递归过滤隐藏项。
     */
    private function filterHidden(Collection $tree, array $hiddenIds): Collection
    {
        return $tree->filter(function ($item) use ($hiddenIds) {
            if (in_array($item->id, $hiddenIds)) {
                return false;
            }
            if ($item->children->isNotEmpty()) {
                $item->setRelation('children',
                    $this->filterHidden($item->children, $hiddenIds)
                );
                if (!$item->url && $item->children->isEmpty()) {
                    return false;
                }
            }
            return true;
        })->values();
    }

    /**
     * 递归过滤：只保留用户有权限查看的菜单项。
     */
    private function filterByPermission(Collection $tree, User $user): Collection
    {
        return $tree->filter(function ($item) use ($user) {
            // 有权限要求 → 检查
            if ($item->permission_id && $item->permission && !$user->hasPermission($item->permission->slug)) {
                return false;
            }

            // 递归过滤子节点
            if ($item->children->isNotEmpty()) {
                $item->setRelation('children',
                    $this->filterByPermission($item->children, $user)
                );
                // 目录节点无可见子项 → 隐藏
                if (!$item->url && $item->children->isEmpty()) {
                    return false;
                }
            }

            return true;
        })->values();
    }
}
