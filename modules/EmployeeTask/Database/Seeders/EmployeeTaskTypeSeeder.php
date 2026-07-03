<?php

namespace Modules\EmployeeTask\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Illuminate\Support\Str;

class EmployeeTaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        $taskType = EmployeeTaskType::updateOrCreate(
            ['key' => 'employee-task'],
            [ 'name' => 'مهمة عمل']
        );

        EmployeeTaskType::updateOrCreate(
            ['key' => 'project_notification'],
            ['name' => 'إشعار مشروع']
        );
    }
}
