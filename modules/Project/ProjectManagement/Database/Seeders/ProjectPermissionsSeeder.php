<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectPermission;
use Illuminate\Support\Facades\Log;

class ProjectPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            ProjectPermission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'submodule' => $permission['submodule'],
                    'action' => $permission['action'],
                    'title' => [
                        'ar' => $permission['title_ar'],
                        'en' => $permission['title_en'],
                    ],
                    'description' => $permission['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        Log::info('Project permissions seeded successfully', ['count' => count($permissions)]);
        $this->command->info('✓ Project permissions seeded: ' . count($permissions));
    }

    private function getPermissions(): array
    {
        return [
            // ================================================================================================
            // EMPLOYEE MANAGEMENT PERMISSIONS
            // ================================================================================================
            [
                'name' => 'employee.view',
                'submodule' => 'employee',
                'action' => 'view',
                'title_ar' => 'عرض الموظف',
                'title_en' => 'View Employee',
                'description' => 'View employee details in project',
            ],
            [
                'name' => 'employee.list',
                'submodule' => 'employee',
                'action' => 'list',
                'title_ar' => 'قائمة الموظفين',
                'title_en' => 'List Employees',
                'description' => 'View list of employees in project',
            ],
            [
                'name' => 'employee.create',
                'submodule' => 'employee',
                'action' => 'create',
                'title_ar' => 'إنشاء موظف',
                'title_en' => 'Create Employee',
                'description' => 'Add new employee to project',
            ],
            [
                'name' => 'employee.update',
                'submodule' => 'employee',
                'action' => 'update',
                'title_ar' => 'تحديث الموظف',
                'title_en' => 'Update Employee',
                'description' => 'Update employee information in project',
            ],
            [
                'name' => 'employee.delete',
                'submodule' => 'employee',
                'action' => 'delete',
                'title_ar' => 'حذف الموظف',
                'title_en' => 'Delete Employee',
                'description' => 'Remove employee from project',
            ],

            // ================================================================================================
            // ARCHIVE LIBRARY PERMISSIONS
            // ================================================================================================
            [
                'name' => 'archiveLibrary.view',
                'submodule' => 'archiveLibrary',
                'action' => 'view',
                'title_ar' => 'عرض المكتبة الأرشيفية',
                'title_en' => 'View Archive Library',
                'description' => 'View archive library items',
            ],
            [
                'name' => 'archiveLibrary.list',
                'submodule' => 'archiveLibrary',
                'action' => 'list',
                'title_ar' => 'قائمة المكتبة الأرشيفية',
                'title_en' => 'List Archive Library',
                'description' => 'View list of archive library items',
            ],
            [
                'name' => 'archiveLibrary.create',
                'submodule' => 'archiveLibrary',
                'action' => 'create',
                'title_ar' => 'إنشاء عنصر أرشيف',
                'title_en' => 'Create Archive Item',
                'description' => 'Create new archive library item',
            ],
            [
                'name' => 'archiveLibrary.update',
                'submodule' => 'archiveLibrary',
                'action' => 'update',
                'title_ar' => 'تحديث عنصر الأرشيف',
                'title_en' => 'Update Archive Item',
                'description' => 'Update archive library item',
            ],
            [
                'name' => 'archiveLibrary.delete',
                'submodule' => 'archiveLibrary',
                'action' => 'delete',
                'title_ar' => 'حذف عنصر الأرشيف',
                'title_en' => 'Delete Archive Item',
                'description' => 'Delete archive library item',
            ],

            // ================================================================================================
            // PROJECT SETTINGS PERMISSIONS
            // ================================================================================================
            [
                'name' => 'project.view',
                'submodule' => 'project',
                'action' => 'view',
                'title_ar' => 'عرض المشروع',
                'title_en' => 'View Project',
                'description' => 'View project details',
            ],
            [
                'name' => 'project.update',
                'submodule' => 'project',
                'action' => 'update',
                'title_ar' => 'تحديث المشروع',
                'title_en' => 'Update Project',
                'description' => 'Update project settings',
            ],
            [
                'name' => 'project.delete',
                'submodule' => 'project',
                'action' => 'delete',
                'title_ar' => 'حذف المشروع',
                'title_en' => 'Delete Project',
                'description' => 'Delete project',
            ],

            // ================================================================================================
            // ROLE MANAGEMENT PERMISSIONS
            // ================================================================================================
            [
                'name' => 'role.view',
                'submodule' => 'role',
                'action' => 'view',
                'title_ar' => 'عرض الدور',
                'title_en' => 'View Role',
                'description' => 'View role details',
            ],
            [
                'name' => 'role.list',
                'submodule' => 'role',
                'action' => 'list',
                'title_ar' => 'قائمة الأدوار',
                'title_en' => 'List Roles',
                'description' => 'View list of project roles',
            ],
            [
                'name' => 'role.create',
                'submodule' => 'role',
                'action' => 'create',
                'title_ar' => 'إنشاء دور',
                'title_en' => 'Create Role',
                'description' => 'Create new project role',
            ],
            [
                'name' => 'role.update',
                'submodule' => 'role',
                'action' => 'update',
                'title_ar' => 'تحديث الدور',
                'title_en' => 'Update Role',
                'description' => 'Update project role',
            ],
            [
                'name' => 'role.delete',
                'submodule' => 'role',
                'action' => 'delete',
                'title_ar' => 'حذف الدور',
                'title_en' => 'Delete Role',
                'description' => 'Delete project role',
            ],
            [
                'name' => 'role.assignPermission',
                'submodule' => 'role',
                'action' => 'assignPermission',
                'title_ar' => 'تعيين صلاحية للدور',
                'title_en' => 'Assign Permission to Role',
                'description' => 'Assign permissions to project role',
            ],

            // ================================================================================================
            // TASK MANAGEMENT PERMISSIONS (Example - Add more as needed)
            // ================================================================================================
            [
                'name' => 'task.view',
                'submodule' => 'task',
                'action' => 'view',
                'title_ar' => 'عرض المهمة',
                'title_en' => 'View Task',
                'description' => 'View task details',
            ],
            [
                'name' => 'task.list',
                'submodule' => 'task',
                'action' => 'list',
                'title_ar' => 'قائمة المهام',
                'title_en' => 'List Tasks',
                'description' => 'View list of tasks',
            ],
            [
                'name' => 'task.create',
                'submodule' => 'task',
                'action' => 'create',
                'title_ar' => 'إنشاء مهمة',
                'title_en' => 'Create Task',
                'description' => 'Create new task',
            ],
            [
                'name' => 'task.update',
                'submodule' => 'task',
                'action' => 'update',
                'title_ar' => 'تحديث المهمة',
                'title_en' => 'Update Task',
                'description' => 'Update task',
            ],
            [
                'name' => 'task.delete',
                'submodule' => 'task',
                'action' => 'delete',
                'title_ar' => 'حذف المهمة',
                'title_en' => 'Delete Task',
                'description' => 'Delete task',
            ],
        ];
    }
}
