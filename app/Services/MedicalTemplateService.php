<?php

namespace App\Services;

use App\MedicalTemplate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicalTemplateService
{
    /**
     * Get filtered template list for DataTables.
     */
    public function getTemplateList(array $filters): Collection
    {
        $query = DB::table('medical_templates')
            ->leftJoin('users', 'users.id', 'medical_templates.created_by')
            ->whereNull('medical_templates.deleted_at')
            ->select('medical_templates.*', 'users.surname as creator_name');

        if (!empty($filters['category'])) {
            $query->where('medical_templates.category', $filters['category']);
        }

        if (!empty($filters['type'])) {
            $query->where('medical_templates.type', $filters['type']);
        }

        // 学科分类筛选：点一级分类要能看到它整棵子树下的模板，
        // 否则父节点永远是空的，前台以为没归好类。
        // 'none' 必须先判：它是个非空字符串，落到下面那个分支会被 (int) 成 0，
        // 于是 categorySubtreeIds(0) 把所有一级分类当成了子树。
        $categoryFilter = $filters['template_category_id'] ?? null;
        if ($categoryFilter === 'none') {
            $query->whereNull('medical_templates.template_category_id');
        } elseif (!empty($categoryFilter) && is_numeric($categoryFilter)) {
            $query->whereIn(
                'medical_templates.template_category_id',
                $this->categorySubtreeIds((int) $categoryFilter)
            );
        }

        return $query->orderBy('medical_templates.usage_count', 'desc')->get();
    }

    /**
     * 一个分类连同它所有后代的 id。
     *
     * 分类是个位到几十的量级，一次拉全表在内存里走比递归查库省事得多，
     * 也避免了 MySQL 5.7 没有递归 CTE 的问题（本项目最低支持 5.7）。
     */
    private function categorySubtreeIds(int $rootId): array
    {
        $pairs = DB::table('template_categories')
            ->whereNull('deleted_at')
            ->select('id', 'parent_id')
            ->get();

        $childrenOf = [];
        foreach ($pairs as $row) {
            $childrenOf[$row->parent_id ?? 0][] = $row->id;
        }

        $ids = [];
        $stack = [$rootId];
        // 计数上限兜底：数据真出环时不该在这里死循环
        $guard = 0;
        while ($stack && $guard++ < 10000) {
            $id = array_pop($stack);
            if (isset($ids[$id])) {
                continue;
            }
            $ids[$id] = true;
            foreach ($childrenOf[$id] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return array_keys($ids);
    }

    /**
     * Create a new medical template.
     */
    public function createTemplate(array $data, int $userId): ?MedicalTemplate
    {
        $content = $data['content'];
        if (is_array($content)) {
            $content = json_encode($content);
        }

        $code = $data['code'] ?? null;
        $autoCode = empty($code) && ($data['category'] ?? '') === 'personal';
        if ($autoCode) {
            $code = $this->generatePersonalCode($userId);
        }

        $attrs = [
            'name' => $data['name'],
            'code' => $code,
            'category' => $data['category'],
            // 学科分类（与上一行的归属范围不是一回事，见 TemplateCategory）
            'template_category_id' => $data['template_category_id'] ?? null,
            'type' => $data['type'],
            'content' => $content,
            'department' => $data['department'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $userId,
            '_who_added' => $userId,
        ];

        try {
            return MedicalTemplate::create($attrs);
        } catch (QueryException $e) {
            // Retry once with next code on duplicate key (race condition)
            if ($autoCode && $e->errorInfo[1] == 1062) {
                $attrs['code'] = $this->generatePersonalCode($userId);
                return MedicalTemplate::create($attrs);
            }
            throw $e;
        }
    }

    /**
     * Generate auto-incremented code (tpl_1, tpl_2, ...) for personal templates.
     */
    private function generatePersonalCode(int $userId): string
    {
        $maxCode = DB::table('medical_templates')
            ->where('created_by', $userId)
            ->where('category', 'personal')
            ->where('code', 'LIKE', 'tpl_%')
            ->whereNull('deleted_at')
            ->selectRaw("MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $next = ($maxCode ?? 0) + 1;

        return 'tpl_' . $next;
    }

    /**
     * Get a single template with creator relation.
     */
    public function getTemplateDetail(int $id): MedicalTemplate
    {
        return MedicalTemplate::with('creator')->findOrFail($id);
    }

    /**
     * Update an existing medical template.
     */
    public function updateTemplate(int $id, array $data): bool
    {
        $content = $data['content'];
        if (is_array($content)) {
            $content = json_encode($content);
        }

        return (bool) MedicalTemplate::where('id', $id)->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'category' => $data['category'],
            // 学科分类（与上一行的归属范围不是一回事，见 TemplateCategory）
            'template_category_id' => $data['template_category_id'] ?? null,
            'type' => $data['type'],
            'content' => $content,
            'department' => $data['department'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Delete a medical template (soft-delete).
     */
    public function deleteTemplate(int $id): bool
    {
        return (bool) MedicalTemplate::where('id', $id)->delete();
    }

    /**
     * Search templates for quick insertion.
     */
    public function searchTemplates(int $userId, string $type, string $keyword = ''): Collection
    {
        $query = MedicalTemplate::active()
            ->availableToUser($userId)
            ->byType($type)
            ->select('id', 'name', 'code', 'category', 'content', 'description', 'usage_count');

        if ($keyword) {
            $query->where(function ($qb) use ($keyword) {
                $qb->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        return $query->orderBy('usage_count', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Increment usage count for a template.
     */
    public function incrementUsage(int $id): int
    {
        $template = MedicalTemplate::findOrFail($id);
        $template->incrementUsage();

        return $template->usage_count;
    }
}
