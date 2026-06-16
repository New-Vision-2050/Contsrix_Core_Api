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
    }
}
