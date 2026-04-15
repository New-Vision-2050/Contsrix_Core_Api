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

        // Group by category, then by submodule
        $result = [];
        $groupedBySubmodule = collect($modified)->groupBy('submodule');

        foreach ($groupedBySubmodule as $submodule => $group) {
            $categoryName = $this->getCategoryName($submodule);
            
            if (!isset($result[$categoryName])) {
                $result[$categoryName] = [];
            }
            
            $result[$categoryName][$submodule] = $group->values()->toArray();
        }

        return $result;
    }

    /**
     * Get category name for submodule grouping
     */
    private function getCategoryName(string $submodule): string
    {
        $categories = [
            'employee' => [
                'ar' => 'إدارة الموظفين',
                'en' => 'Employee Management',
            ],
            'archive-library' => [
                'ar' => 'المكتبة الأرشيفية',
                'en' => 'Archive Library',
            ],
            'archive-cycle' => [
                'ar' => 'دورة الأرشيف',
                'en' => 'Archive Cycle',
            ],
            'role' => [
                'ar' => 'إدارة الأدوار',
                'en' => 'Role Management',
            ],
            'settings' => [
                'ar' => 'الإعدادات',
                'en' => 'Settings',
            ],
            'task' => [
                'ar' => 'إدارة المهام',
                'en' => 'Task Management',
            ],
            'budget' => [
                'ar' => 'إدارة الميزانية',
                'en' => 'Budget Management',
            ],
            'expense' => [
                'ar' => 'إدارة المصروفات',
                'en' => 'Expense Management',
            ],
            'report' => [
                'ar' => 'التقارير',
                'en' => 'Reports',
            ],
        ];

        $locale = app()->getLocale();
        return $categories[$submodule][$locale] ?? ucfirst(str_replace('-', ' ', $submodule));
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
            'archive-library' => [
                'ar' => 'المكتبة الأرشيفية',
                'en' => 'Archive Library',
            ],
            'archiveLibrary' => [
                'ar' => 'المكتبة الأرشيفية',
                'en' => 'Archive Library',
            ],
            'archive-cycle' => [
                'ar' => 'دورة الأرشيف',
                'en' => 'Archive Cycle',
            ],
            'archiveCycle' => [
                'ar' => 'دورة الأرشيف',
                'en' => 'Archive Cycle',
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
