<?php

declare(strict_types=1);

namespace Modules\Reports\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reports\Models\ReportTemplate;

/** @extends Factory<ReportTemplate> */
class ReportTemplateFactory extends Factory
{
    protected $model = ReportTemplate::class;

    public function definition(): array
    {
        return [
            'name' => [
                'ar' => 'قالب اختبار',
                'en' => 'Test Template',
            ],
            'description' => [
                'ar' => 'وصف القالب',
                'en' => 'Template description',
            ],
            'config'    => [],
            'is_active' => true,
        ];
    }
}
