<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReason;

class ProjectNotificationWorkStoppageReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name_ar' => 'عدم وجود/ خلل في إجراءات تصاريح العمل', 'name_en' => 'Missing or incorrect work permit procedures'],
            ['name_ar' => 'عدم حمل البطاقات التعريفية في موقع العمل', 'name_en' => 'ID cards not carried at work site'],
            ['name_ar' => 'نقص مهمات الوقاية الشخصية', 'name_en' => 'Lack of personal protective equipment'],
            ['name_ar' => 'عدم اكتمال تقييم المخاطر', 'name_en' => 'Incomplete risk assessment'],
            ['name_ar' => 'خلل في تطبيق نظام العزل والبطاقات التحذيرية', 'name_en' => 'Failure in isolation and warning tag system'],
            ['name_ar' => 'عدم وجود إجراءات العمل الآمن في الموقع', 'name_en' => 'No safe work procedures at site'],
            ['name_ar' => 'وجود معدات رفع غير مطابقة', 'name_en' => 'Non-compliant lifting equipment'],
            ['name_ar' => 'عدم مطابقة السلالم أو السقالات لمعايير السلامة', 'name_en' => 'Ladders or scaffolding not meeting safety standards'],
            ['name_ar' => 'عدم ارتداء بدلة القوس الكهربائي المناسبة', 'name_en' => 'Not wearing appropriate arc flash suit'],
            ['name_ar' => 'ملاحظات أخرى', 'name_en' => 'Other notes'],
        ];

        foreach ($reasons as $index => $reason) {
            ProjectNotificationWorkStoppageReason::query()->firstOrCreate(
                ['name_ar' => $reason['name_ar']],
                [
                    'name_en' => $reason['name_en'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
