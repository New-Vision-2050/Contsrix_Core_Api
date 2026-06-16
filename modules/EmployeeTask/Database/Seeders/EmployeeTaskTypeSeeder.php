<?php

namespace Modules\EmployeeTask\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\DTO\CreateProjectManagementDTO;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\User\Models\User;
class EmployeeTaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        $taskType = EmployeeTaskType::firstOrCreate(
            ['key' => 'employee-task'],
            ['id' => (string) Str::uuid(), 'name' => 'Employee Task']
        );

        $user = User::first();
        if (!$user) {
            $this->command->warn('No users found. Skipping seeder.');
            return;
        }

        $projectDto = new CreateProjectManagementDTO(
            projectTypeId: 1,
            subProjectTypeId: 1,
            subSubProjectTypeId: 1,
            name: 'مشروع تجريبي لمهمة الموظف',
            branchId: $user->management_hierarchy_id,
            status: 1
        );

        $project = new ProjectManagement($projectDto->toArray());
        $project->id = (string) Str::uuid();
        $project->save();

        EmployeeTaskRequest::create([
            'id' => (string) Str::uuid(),
            'serial_number' => 'TASK-2026-00001',
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'title' => 'مهمة تجريبية مرتبطة بمشروع',
            'description' => 'تم إنشاؤها بواسطة Seeder للتأكد من عمل item_type و item_id',
            'status' => 'pending',
            'task_date' => now()->format('Y-m-d'),
            'duration_hours' => 4,
            'task_latitude' => 24.7136,
            'task_longitude' => 46.6753,
            'employee_task_type_id' => $taskType->id,
            'item_type' => ProjectManagement::class,
            'item_id' => $project->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}
