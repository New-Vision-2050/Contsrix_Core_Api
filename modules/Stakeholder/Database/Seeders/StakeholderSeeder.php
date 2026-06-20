<?php

namespace Modules\Stakeholder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Stakeholder\Models\Stakeholder;

class StakeholderSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'شركه ابعاد الرؤيه للمختبرات',
            'شركه نيو فيجن لتكنلوجيا المعلومات',
            'شركه نيوفيجن للاستشارات الهندسية',
            'شركه انتراكت للمقاولات',
            'شركه اركان الاتقان',
            'شركة بايت جارد',
            'شركة اخري تتطلب نقل كفالة',
            'شركه ابعاد الرؤيه فرع مكة',
            'شركة ترابط للمقاولات',
            'شركه ابعاد الرؤيه للاستشارات الهندسية',
            'شركة حلول',
        ];

        foreach ($names as $name) {
            Stakeholder::firstOrCreate(
                ['name' => $name],
                ['status' => 1,"name"=>$name]
            );
        }
    }
}
