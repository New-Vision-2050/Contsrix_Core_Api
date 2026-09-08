<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectNotificationType;

class ProjectNotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name_ar' => 'جهد متوسط كابلات هوائي', 'name_en' => 'Medium voltage aerial cables', 'type' => 'electricity'],
            ['name_ar' => 'جهد متوسط كابلات ارضي', 'name_en' => 'Medium voltage ground cables', 'type' => 'electricity'],
            ['name_ar' => 'جهد منخفض كابلات ارضي', 'name_en' => 'Low voltage ground cables', 'type' => 'electricity'],
            ['name_ar' => 'جهد منخفض كابلات هوائي', 'name_en' => 'Low voltage aerial cables', 'type' => 'electricity'],
            ['name_ar' => 'جهد متوسط معدات هوائي', 'name_en' => 'Medium voltage aerial equipment', 'type' => 'electricity'],
            ['name_ar' => 'جهد متوسط معدات ارضي', 'name_en' => 'Medium voltage ground equipment', 'type' => 'electricity'],
            ['name_ar' => 'جهد منخفض معدات ارضي', 'name_en' => 'Low voltage ground equipment', 'type' => 'electricity'],
            ['name_ar' => 'جهد منخفض معدات هوائي', 'name_en' => 'Low voltage aerial equipment', 'type' => 'electricity'],
            ['name_ar' => 'كسر رئيسي', 'name_en' => 'Main break', 'type' => 'water'],
            ['name_ar' => 'كسر خط فرعي', 'name_en' => 'Branch line break', 'type' => 'water'],
            ['name_ar' => 'كسر خط رئيسي', 'name_en' => 'Main line break', 'type' => 'water'],
            ['name_ar' => 'لم يتم العثور علي كسر', 'name_en' => 'No break found', 'type' => 'water'],
            ['name_ar' => 'تغيير صمام', 'name_en' => 'Valve replacement', 'type' => 'water'],
            ['name_ar' => 'كسر ماسورة فرعية', 'name_en' => 'Branch pipe break', 'type' => 'water'],
            ['name_ar' => 'كسر قائم العداد', 'name_en' => 'Meter riser break', 'type' => 'water'],
            ['name_ar' => 'تسريب بالعداد', 'name_en' => 'Meter leak', 'type' => 'water'],
            ['name_ar' => 'انقطاع', 'name_en' => 'Interruption', 'type' => 'water'],
            ['name_ar' => 'غسيل', 'name_en' => 'Flushing', 'type' => 'water'],
        ];

        foreach ($types as $index => $type) {
            ProjectNotificationType::query()->firstOrCreate(
                ['name_ar' => $type['name_ar']],
                [
                    'name_en' => $type['name_en'],
                    'type' => $type['type'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
