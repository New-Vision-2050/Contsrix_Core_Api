<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Illuminate\Support\Collection;

class ProjectPermissionLookupPresenter
{
    /**
     * Present project permissions in hierarchical tree structure
     * Similar to PermissionLookupPresenter but for project-specific permissions
     */
    public function present(Collection $permissions): array
    {
        $modified = [];
        
        foreach ($permissions as $permission) {
            // Get translated title from JSON field
            $titleAr = $permission->getTranslation('title', 'ar');
            $titleEn = $permission->getTranslation('title', 'en');
            
            // Use current locale title
            $translatedName = app()->getLocale() === 'ar' ? $titleAr : $titleEn;
            
            $modified[] = [
                "id" => $permission->id,
                "key" => $permission->name,
                "submodule" => $permission->submodule,
                "action" => $permission->action,
                "type" => $permission->action,
                "name" => $translatedName,
                "title_ar" => $titleAr,
                "title_en" => $titleEn,
            ];
        }

        // Group by submodule first, then by action within each submodule
        $groupedBySubmodule = collect($modified)->groupBy('submodule');

        return $groupedBySubmodule->map(function ($group, $submodule) {
            // Get the localized submodule name
            $submoduleName = $this->getSubmoduleName($submodule);
            
            return [
                'name' => $submoduleName,
                'key' => $submodule,
                'permissions' => $group->values()->toArray(),
                'count' => $group->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Get translated submodule name
     */
    private function getSubmoduleName(string $submodule): string
    {
        $translations = [
            'employee' => [
                'ar' => 'الموظفين',
                'en' => 'Employees',
            ],
            'archiveLibrary' => [
                'ar' => 'المكتبة الأرشيفية',
                'en' => 'Archive Library',
            ],
            'project' => [
                'ar' => 'المشروع',
                'en' => 'Project',
            ],
            'settings' => [
                'ar' => 'الإعدادات',
                'en' => 'Settings',
            ],
            'role' => [
                'ar' => 'الأدوار',
                'en' => 'Roles',
            ],
            'task' => [
                'ar' => 'المهام',
                'en' => 'Tasks',
            ],
            'budget' => [
                'ar' => 'الميزانية',
                'en' => 'Budget',
            ],
            'expense' => [
                'ar' => 'المصروفات',
                'en' => 'Expenses',
            ],
            'report' => [
                'ar' => 'التقارير',
                'en' => 'Reports',
            ],
        ];

        $locale = app()->getLocale();
        return $translations[$submodule][$locale] ?? ucfirst($submodule);
    }

    /**
     * Present permissions in flat format for dropdowns
     */
    public function presentFlat(Collection $permissions): array
    {
        return $permissions->map(function ($permission) {
            $titleAr = $permission->getTranslation('title', 'ar');
            $titleEn = $permission->getTranslation('title', 'en');
            $translatedName = app()->getLocale() === 'ar' ? $titleAr : $titleEn;

            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'title' => $translatedName,
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'submodule' => $permission->submodule,
                'action' => $permission->action,
            ];
        })->toArray();
    }
}
