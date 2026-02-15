<?php

namespace App\Services;

use App\MedicalTemplate;
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

        return $query->orderBy('medical_templates.usage_count', 'desc')->get();
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

        return MedicalTemplate::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'category' => $data['category'],
            'type' => $data['type'],
            'content' => $content,
            'department' => $data['department'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $userId,
            '_who_added' => $userId,
        ]);
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
