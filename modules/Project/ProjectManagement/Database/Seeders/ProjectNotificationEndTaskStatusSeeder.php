<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectNotificationEndTaskStatus;

class ProjectNotificationEndTaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'key' => 'malfunction',
                'name_ar' => 'معطل',
                'name_en' => 'Malfunction',
            ],
            [
                'key' => 'shift_handover',
                'name_ar' => 'تسليم شيفت',
                'name_en' => 'Shift handover',
            ],
            [
                'key' => 'work_completion',
                'name_ar' => 'انهاء الاعمال',
                'name_en' => 'Work completion',
            ],
        ];

        foreach ($statuses as $index => $status) {
            ProjectNotificationEndTaskStatus::query()->firstOrCreate(
                ['key' => $status['key']],
                [
                    'name_ar' => $status['name_ar'],
                    'name_en' => $status['name_en'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
