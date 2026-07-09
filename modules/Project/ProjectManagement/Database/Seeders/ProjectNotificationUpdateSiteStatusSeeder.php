<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectNotificationUpdateSiteStatus;

class ProjectNotificationUpdateSiteStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'key' => 'digging_in_progress',
                'name_ar' => 'جاري الحفر',
                'name_en' => 'Digging in progress',
            ],
            [
                'key' => 'extension_in_progress',
                'name_ar' => 'جار التمديد',
                'name_en' => 'Extension in progress',
            ],
            [
                'key' => 'welding_in_progress',
                'name_ar' => 'جاري اللحام',
                'name_en' => 'Welding in progress',
            ],
            [
                'key' => 'waiting_for_permits',
                'name_ar' => 'بانتظار التصاريح',
                'name_en' => 'Waiting for permits',
            ],
            [
                'key' => 'contractor_not_started',
                'name_ar' => 'المقاول لم يبدء',
                'name_en' => 'Contractor has not started',
            ],
            [
                'key' => 'contractor_postponed',
                'name_ar' => 'المقاول اجل',
                'name_en' => 'Contractor postponed',
            ],
            [
                'key' => 'contractor_apologized_stopped',
                'name_ar' => 'المقاول اعتذر متوقف',
                'name_en' => 'Contractor apologized, work stopped',
            ],
            [
                'key' => 'obstacle_present',
                'name_ar' => 'وجود عائق',
                'name_en' => 'Obstacle present',
            ],
            [
                'key' => 'completed_waiting_for_operation',
                'name_ar' => 'تم الانتهاء بانتظار التشغيل',
                'name_en' => 'Completed, waiting for operation',
            ],
            [
                'key' => 'operated',
                'name_ar' => 'تم التشغيل',
                'name_en' => 'Operated',
            ],
            [
                'key' => 'execution_difficulty',
                'name_ar' => 'صعوبه التنفيذ',
                'name_en' => 'Execution difficulty',
            ],
        ];

        foreach ($statuses as $index => $status) {
            ProjectNotificationUpdateSiteStatus::query()->firstOrCreate(
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
