<?php

namespace Modules\EmployeeTask\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Illuminate\Support\Str;

class EmployeeTaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        $taskType = EmployeeTaskType::firstOrCreate(
            ['key' => 'employee-task'],
            ['id' => (string) Str::uuid(), 'name' => 'Employee Task']
        );

        EmployeeTaskType::firstOrCreate(
            ['key' => 'project_notification'],
            ['id' => (string) Str::uuid(), 'name' => 'إشعار مشروع']
        );
    }
}
