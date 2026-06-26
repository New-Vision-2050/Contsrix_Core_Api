<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectPermission;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Illuminate\Support\Facades\Log;

class ProjectPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Get permissions from config file - use the module's config path
        // Laravel loads module configs as: module-name.key
        $configPermissions = config('projectmanagement.permissions', []);

        if (empty($configPermissions)) {
            Log::warning('No permissions found in projectmanagement config');
            $this->command->warn('⚠ No permissions found in config file');
            return;
        }

        $createdCount = 0;
        $newPermissionIds = [];

        foreach ($configPermissions as $key => $name) {
            // Parse permission name to extract submodule and action
            // Format: project-management.project-management*submodule.action
            $parts = explode('.', $name);

            if (count($parts) < 3) {
                Log::warning("Invalid permission format for: {$name}");
                continue;
            }

            // Extract submodule (from middle part after *)
            $middlePart = $parts[1] ?? '';
            $submoduleParts = explode('*', $middlePart);
            $submodule = $submoduleParts[1] ?? '';

            // Extract action (last part)
            $action = $parts[2] ?? '';

            // Auto-generate translations based on key and action
            $translations = $this->generateTranslations($key, $submodule, $action);

            // Create permission with JSON encoded title for HasTranslations trait
            $permission = ProjectPermission::updateOrCreate(
                ['name' => $name],
                [
                    'submodule' => $submodule,
                    'action' => $action,
                    'title' => [
                        'ar' => $translations['title_ar'],
                        'en' => $translations['title_en'],
                    ],
                    'description' => $translations['description'],
                    'is_active' => true,
                ]
            );

            // Track newly created permissions
            if ($permission->wasRecentlyCreated) {
                $newPermissionIds[] = $permission->id;
            }

            $createdCount++;
        }

        // Assign new permissions to all Project Admin roles
        if (!empty($newPermissionIds)) {
            $this->assignPermissionsToProjectAdminRoles($newPermissionIds);
        }

        Log::info('Project permissions seeded successfully', ['count' => $createdCount, 'new_permissions' => count($newPermissionIds)]);
        $this->command->info("✓ Project permissions seeded: {$createdCount}");

        if (!empty($newPermissionIds)) {
            $this->command->info("✓ Assigned " . count($newPermissionIds) . " new permissions to all Project Admin roles");
        }
    }

    /**
     * Assign new permissions to all Project Admin roles across all projects
     */
    private function assignPermissionsToProjectAdminRoles(array $permissionIds): void
    {
        try {
            // Find all Project Admin roles (is_default = true)
            $adminRoles = ProjectRole::where('is_default', true)->get();

            if ($adminRoles->isEmpty()) {
                Log::warning('No Project Admin roles found to assign permissions');
                $this->command->warn('⚠ No Project Admin roles found');
                return;
            }

            $rolesUpdated = 0;

            foreach ($adminRoles as $adminRole) {
                // Get existing permission IDs
                $existingPermissionIds = $adminRole->permissions()->pluck('project_permission_id')->toArray();

                // Merge with new permissions (avoid duplicates)
                $allPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));

                // Sync all permissions to the role
                $adminRole->permissions()->sync($allPermissionIds);

                $rolesUpdated++;

                Log::info('Assigned new permissions to Project Admin role', [
                    'role_id' => $adminRole->id,
                    'project_id' => $adminRole->project_id,
                    'new_permissions_count' => count($permissionIds),
                    'total_permissions' => count($allPermissionIds),
                ]);
            }

            Log::info('Successfully assigned permissions to all Project Admin roles', [
                'roles_updated' => $rolesUpdated,
                'permissions_assigned' => count($permissionIds),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign permissions to Project Admin roles: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->command->error('✗ Failed to assign permissions to Project Admin roles: ' . $e->getMessage());
        }
    }

    /**
     * Auto-generate translations based on permission key
     */
    private function generateTranslations(string $key, string $submodule, string $action): array
    {
        // Translation mappings for actions
        $actionTranslations = [
            'view' => ['ar' => 'عرض', 'en' => 'View'],
            'list' => ['ar' => 'قائمة', 'en' => 'List'],
            'create' => ['ar' => 'إنشاء', 'en' => 'Create'],
            'update' => ['ar' => 'تحديث', 'en' => 'Update'],
            'delete' => ['ar' => 'حذف', 'en' => 'Delete'],
            'assign' => ['ar' => 'تعيين', 'en' => 'Assign'],
            'export' => ['ar' => 'تصدير', 'en' => 'Export'],
            'activate' => ['ar' => 'تفعيل', 'en' => 'Activate'],
        ];

        // Translation mappings for submodules
        $submoduleTranslations = [
            'employee' => ['ar' => 'الموظف', 'en' => 'Employee'],
            'archive-library' => ['ar' => 'المكتبة الأرشيفية', 'en' => 'Archive Library'],
            'archiveLibrary' => ['ar' => 'المكتبة الأرشيفية', 'en' => 'Archive Library'],
            'archive-cycle' => ['ar' => 'دورة الأرشيف', 'en' => 'Archive Cycle'],
            'archiveCycle' => ['ar' => 'دورة الأرشيف', 'en' => 'Archive Cycle'],
            'attachment-cycle-settings' => ['ar' => 'إعدادات دورة المرفقات', 'en' => 'Attachment Cycle Settings'],
            'archive-library-settings' => ['ar' => 'إعدادات المكتبة الأرشيفية', 'en' => 'Archive Library Settings'],
            'role' => ['ar' => 'الدور', 'en' => 'Role'],
            'project-share' => ['ar' => 'مشاركة المشروع', 'en' => 'Project Share'],
            'notifications' => ['ar' => 'الإشعارات', 'en' => 'Notifications'],
            'roles-and-permissions-settings' => ['ar' => 'إعدادات الأدوار والصلاحيات', 'en' => 'Roles and Permissions Settings'],
            'project-sharing-settings' => ['ar' => 'إعدادات مشاركة المشاريع', 'en' => 'Project Sharing Settings'],
            'settings' => ['ar' => 'الإعدادات', 'en' => 'Settings'],
            'task' => ['ar' => 'المهمة', 'en' => 'Task'],
            'budget' => ['ar' => 'الميزانية', 'en' => 'Budget'],
            'expense' => ['ar' => 'المصروف', 'en' => 'Expense'],
            'report' => ['ar' => 'التقرير', 'en' => 'Report'],
        ];

        $actionAr = $actionTranslations[$action]['ar'] ?? ucfirst($action);
        $actionEn = $actionTranslations[$action]['en'] ?? ucfirst($action);

        $submoduleAr = $submoduleTranslations[$submodule]['ar'] ?? ucfirst($submodule);
        $submoduleEn = $submoduleTranslations[$submodule]['en'] ?? ucfirst($submodule);

        return [
            'title_ar' => "{$actionAr} {$submoduleAr}",
            'title_en' => "{$actionEn} {$submoduleEn}",
            'description' => "{$actionEn} {$submoduleEn} in project",
        ];
    }

    /**
     * Get permissions data with translations and metadata
     * Maps config keys to permission details
     */
//    private function getPermissionsData(): array
//    {
//        return [];
//        return [
//            // ================================================================================================
//            // EMPLOYEE MANAGEMENT PERMISSIONS
//            // ================================================================================================
//            'PROJECT_EMPLOYEE_VIEW' => [
//                'submodule' => 'employee',
//                'action' => 'view',
//                'title_ar' => 'عرض الموظف',
//                'title_en' => 'View Employee',
//                'description' => 'View employee details in project',
//            ],
//            'PROJECT_EMPLOYEE_LIST' => [
//                'submodule' => 'employee',
//                'action' => 'list',
//                'title_ar' => 'قائمة الموظفين',
//                'title_en' => 'List Employees',
//                'description' => 'View list of employees in project',
//            ],
//            'PROJECT_EMPLOYEE_CREATE' => [
//                'submodule' => 'employee',
//                'action' => 'create',
//                'title_ar' => 'إنشاء موظف',
//                'title_en' => 'Create Employee',
//                'description' => 'Add new employee to project',
//            ],
//            'PROJECT_EMPLOYEE_UPDATE' => [
//                'submodule' => 'employee',
//                'action' => 'update',
//                'title_ar' => 'تحديث الموظف',
//                'title_en' => 'Update Employee',
//                'description' => 'Update employee information in project',
//            ],
//            'PROJECT_EMPLOYEE_DELETE' => [
//                'submodule' => 'employee',
//                'action' => 'delete',
//                'title_ar' => 'حذف الموظف',
//                'title_en' => 'Delete Employee',
//                'description' => 'Remove employee from project',
//            ],
//            'PROJECT_EMPLOYEE_ASSIGN' => [
//                'submodule' => 'employee',
//                'action' => 'assign',
//                'title_ar' => 'تعيين موظف',
//                'title_en' => 'Assign Employee',
//                'description' => 'Assign employee to project',
//            ],
//
//            // ================================================================================================
//            // ARCHIVE LIBRARY PERMISSIONS
//            // ================================================================================================
//            'PROJECT_ARCHIVE_VIEW' => [
//                'submodule' => 'archiveLibrary',
//                'action' => 'view',
//                'title_ar' => 'عرض المكتبة الأرشيفية',
//                'title_en' => 'View Archive Library',
//                'description' => 'View archive library items',
//            ],
//            'PROJECT_ARCHIVE_LIST' => [
//                'submodule' => 'archiveLibrary',
//                'action' => 'list',
//                'title_ar' => 'قائمة المكتبة الأرشيفية',
//                'title_en' => 'List Archive Library',
//                'description' => 'View list of archive library items',
//            ],
//            'PROJECT_ARCHIVE_CREATE' => [
//                'submodule' => 'archiveLibrary',
//                'action' => 'create',
//                'title_ar' => 'إنشاء عنصر أرشيف',
//                'title_en' => 'Create Archive Item',
//                'description' => 'Create new archive library item',
//            ],
//            'PROJECT_ARCHIVE_UPDATE' => [
//                'submodule' => 'archiveLibrary',
//                'action' => 'update',
//                'title_ar' => 'تحديث عنصر الأرشيف',
//                'title_en' => 'Update Archive Item',
//                'description' => 'Update archive library item',
//            ],
//            'PROJECT_ARCHIVE_DELETE' => [
//                'submodule' => 'archiveLibrary',
//                'action' => 'delete',
//                'title_ar' => 'حذف عنصر الأرشيف',
//                'title_en' => 'Delete Archive Item',
//                'description' => 'Delete archive library item',
//            ],
//            'PROJECT_ARCHIVE_DOWNLOAD' => [
//                'submodule' => 'archiveLibrary',
//                'action' => 'download',
//                'title_ar' => 'تحميل عنصر الأرشيف',
//                'title_en' => 'Download Archive Item',
//                'description' => 'Download archive library item',
//            ],
//            'PROJECT_ARCHIVE_UPLOAD' => [
//                'submodule' => 'archiveLibrary',
//                'action' => 'upload',
//                'title_ar' => 'رفع عنصر الأرشيف',
//                'title_en' => 'Upload Archive Item',
//                'description' => 'Upload archive library item',
//            ],
//
//            // ================================================================================================
//            // PROJECT SETTINGS PERMISSIONS
//            // ================================================================================================
//            'PROJECT_SETTINGS_VIEW' => [
//                'submodule' => 'settings',
//                'action' => 'view',
//                'title_ar' => 'عرض إعدادات المشروع',
//                'title_en' => 'View Project Settings',
//                'description' => 'View project settings',
//            ],
//            'PROJECT_SETTINGS_UPDATE' => [
//                'submodule' => 'settings',
//                'action' => 'update',
//                'title_ar' => 'تحديث إعدادات المشروع',
//                'title_en' => 'Update Project Settings',
//                'description' => 'Update project settings',
//            ],
//            'PROJECT_SETTINGS_DELETE' => [
//                'submodule' => 'settings',
//                'action' => 'delete',
//                'title_ar' => 'حذف إعدادات المشروع',
//                'title_en' => 'Delete Project Settings',
//                'description' => 'Delete project settings',
//            ],
//
//            // ================================================================================================
//            // ROLE MANAGEMENT PERMISSIONS
//            // ================================================================================================
//            'PROJECT_ROLE_VIEW' => [
//                'submodule' => 'role',
//                'action' => 'view',
//                'title_ar' => 'عرض الدور',
//                'title_en' => 'View Role',
//                'description' => 'View role details',
//            ],
//            'PROJECT_ROLE_LIST' => [
//                'submodule' => 'role',
//                'action' => 'list',
//                'title_ar' => 'قائمة الأدوار',
//                'title_en' => 'List Roles',
//                'description' => 'View list of project roles',
//            ],
//            'PROJECT_ROLE_CREATE' => [
//                'submodule' => 'role',
//                'action' => 'create',
//                'title_ar' => 'إنشاء دور',
//                'title_en' => 'Create Role',
//                'description' => 'Create new project role',
//            ],
//            'PROJECT_ROLE_UPDATE' => [
//                'submodule' => 'role',
//                'action' => 'update',
//                'title_ar' => 'تحديث الدور',
//                'title_en' => 'Update Role',
//                'description' => 'Update project role',
//            ],
//            'PROJECT_ROLE_DELETE' => [
//                'submodule' => 'role',
//                'action' => 'delete',
//                'title_ar' => 'حذف الدور',
//                'title_en' => 'Delete Role',
//                'description' => 'Delete project role',
//            ],
//            'PROJECT_ROLE_ASSIGN_PERMISSION' => [
//                'submodule' => 'role',
//                'action' => 'assignPermission',
//                'title_ar' => 'تعيين صلاحية للدور',
//                'title_en' => 'Assign Permission to Role',
//                'description' => 'Assign permissions to project role',
//            ],
//
//            // ================================================================================================
//            // TASK MANAGEMENT PERMISSIONS
//            // ================================================================================================
//            'PROJECT_TASK_VIEW' => [
//                'submodule' => 'task',
//                'action' => 'view',
//                'title_ar' => 'عرض المهمة',
//                'title_en' => 'View Task',
//                'description' => 'View task details',
//            ],
//            'PROJECT_TASK_LIST' => [
//                'submodule' => 'task',
//                'action' => 'list',
//                'title_ar' => 'قائمة المهام',
//                'title_en' => 'List Tasks',
//                'description' => 'View list of tasks',
//            ],
//            'PROJECT_TASK_CREATE' => [
//                'submodule' => 'task',
//                'action' => 'create',
//                'title_ar' => 'إنشاء مهمة',
//                'title_en' => 'Create Task',
//                'description' => 'Create new task',
//            ],
//            'PROJECT_TASK_UPDATE' => [
//                'submodule' => 'task',
//                'action' => 'update',
//                'title_ar' => 'تحديث المهمة',
//                'title_en' => 'Update Task',
//                'description' => 'Update task',
//            ],
//            'PROJECT_TASK_DELETE' => [
//                'submodule' => 'task',
//                'action' => 'delete',
//                'title_ar' => 'حذف المهمة',
//                'title_en' => 'Delete Task',
//                'description' => 'Delete task',
//            ],
//            'PROJECT_TASK_ASSIGN' => [
//                'submodule' => 'task',
//                'action' => 'assign',
//                'title_ar' => 'تعيين مهمة',
//                'title_en' => 'Assign Task',
//                'description' => 'Assign task to user',
//            ],
//            'PROJECT_TASK_COMPLETE' => [
//                'submodule' => 'task',
//                'action' => 'complete',
//                'title_ar' => 'إكمال مهمة',
//                'title_en' => 'Complete Task',
//                'description' => 'Mark task as complete',
//            ],
//
//            // ================================================================================================
//            // FINANCIAL/BUDGET PERMISSIONS
//            // ================================================================================================
//            'PROJECT_BUDGET_VIEW' => [
//                'submodule' => 'budget',
//                'action' => 'view',
//                'title_ar' => 'عرض الميزانية',
//                'title_en' => 'View Budget',
//                'description' => 'View project budget',
//            ],
//            'PROJECT_BUDGET_UPDATE' => [
//                'submodule' => 'budget',
//                'action' => 'update',
//                'title_ar' => 'تحديث الميزانية',
//                'title_en' => 'Update Budget',
//                'description' => 'Update project budget',
//            ],
//            'PROJECT_EXPENSE_CREATE' => [
//                'submodule' => 'expense',
//                'action' => 'create',
//                'title_ar' => 'إنشاء مصروف',
//                'title_en' => 'Create Expense',
//                'description' => 'Create project expense',
//            ],
//            'PROJECT_EXPENSE_APPROVE' => [
//                'submodule' => 'expense',
//                'action' => 'approve',
//                'title_ar' => 'الموافقة على المصروف',
//                'title_en' => 'Approve Expense',
//                'description' => 'Approve project expense',
//            ],
//
//            // ================================================================================================
//            // REPORTS PERMISSIONS
//            // ================================================================================================
//            'PROJECT_REPORT_VIEW' => [
//                'submodule' => 'report',
//                'action' => 'view',
//                'title_ar' => 'عرض التقارير',
//                'title_en' => 'View Reports',
//                'description' => 'View project reports',
//            ],
//            'PROJECT_REPORT_EXPORT' => [
//                'submodule' => 'report',
//                'action' => 'export',
//                'title_ar' => 'تصدير التقارير',
//                'title_en' => 'Export Reports',
//                'description' => 'Export project reports',
//            ],
//            'PROJECT_REPORT_GENERATE' => [
//                'submodule' => 'report',
//                'action' => 'generate',
//                'title_ar' => 'إنشاء التقارير',
//                'title_en' => 'Generate Reports',
//                'description' => 'Generate project reports',
//            ],
//        ];
//    }
}
