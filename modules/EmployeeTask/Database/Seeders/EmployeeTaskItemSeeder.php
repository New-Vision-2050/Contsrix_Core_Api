<?php

namespace Modules\EmployeeTask\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\EmployeeTask\Models\EmployeeTaskItem;
use Illuminate\Support\Str;

class EmployeeTaskItemSeeder extends Seeder
{
    public function run(): void
    {
        EmployeeTaskItem::firstOrCreate(
            ['key' => 'projects'],
            [
                'id'          => (string) Str::uuid(),
                'name'        => 'المشاريع',
                'model_class' => \Modules\Project\ProjectManagement\Models\ProjectManagement::class,
            ]
        );

        EmployeeTaskItem::firstOrCreate(
            ['key' => 'project_notification'],
            [
                'id'          => (string) Str::uuid(),
                'name'        => 'إشعار مشروع',
                'model_class' => \Modules\Project\ProjectManagement\Models\ProjectNotification::class,
            ]
        );
    }
}
