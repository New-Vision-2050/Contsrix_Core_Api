<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatus;

class ProjectNotificationSiteStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name_ar' => 'بانتظار وصول الفرقة', 'name_en' => 'Waiting for team arrival'],
            ['name_ar' => 'بانتظار المقاول', 'name_en' => 'Waiting for contractor'],
            ['name_ar' => 'بانتظار التصاريح', 'name_en' => 'Waiting for permits'],
            ['name_ar' => 'جاري الفحص', 'name_en' => 'Under inspection'],
            ['name_ar' => 'تم تحديد العطل', 'name_en' => 'Fault identified'],
            ['name_ar' => 'جاري الحفر', 'name_en' => 'Digging in progress'],
            ['name_ar' => 'جاري تمديد / تركيب', 'name_en' => 'Extension / installation in progress'],
            ['name_ar' => 'جاري الاختبار والتشغيل', 'name_en' => 'Testing and commissioning in progress'],
            ['name_ar' => 'تم التشغيل جزئياً', 'name_en' => 'Partially commissioned'],
            ['name_ar' => 'تم التشغيل بالكامل', 'name_en' => 'Fully commissioned'],
            ['name_ar' => 'متوقف بسبب عائق', 'name_en' => 'Stopped due to obstacle'],
        ];

        foreach ($statuses as $index => $status) {
            ProjectNotificationSiteStatus::query()->firstOrCreate(
                ['name_ar' => $status['name_ar']],
                [
                    'name_en' => $status['name_en'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
