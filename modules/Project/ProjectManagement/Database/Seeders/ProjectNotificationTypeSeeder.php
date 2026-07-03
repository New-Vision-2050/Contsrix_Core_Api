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
            ['name_ar' => 'جهد متوسط كابلات هوائي', 'name_en' => 'Medium voltage aerial cables'],
            ['name_ar' => 'جهد متوسط كابلات ارضي', 'name_en' => 'Medium voltage ground cables'],
            ['name_ar' => 'جهد منخفض كابلات ارضي', 'name_en' => 'Low voltage ground cables'],
            ['name_ar' => 'جهد منخفض كابلات هوائي', 'name_en' => 'Low voltage aerial cables'],
            ['name_ar' => 'جهد متوسط معدات هوائي', 'name_en' => 'Medium voltage aerial equipment'],
            ['name_ar' => 'جهد متوسط معدات ارضي', 'name_en' => 'Medium voltage ground equipment'],
            ['name_ar' => 'جهد منخفض معدات ارضي', 'name_en' => 'Low voltage ground equipment'],
            ['name_ar' => 'جهد منخفض معدات هوائي', 'name_en' => 'Low voltage aerial equipment'],
        ];

        foreach ($types as $index => $type) {
            ProjectNotificationType::query()->firstOrCreate(
                ['name_ar' => $type['name_ar']],
                [
                    'name_en' => $type['name_en'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
