<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ContractualEngagement;

class ContractualEngagementSeeder extends Seeder
{
    public function run(): void
    {
        $engagements = [
            ['name_ar' => 'العقد الموحد بجدة', 'name_en' => 'Unified Contract in Jeddah'],
            ['name_ar' => 'العقد الموحد بمكة', 'name_en' => 'Unified Contract in Makkah'],
        ];

        foreach ($engagements as $index => $engagement) {
            ContractualEngagement::query()->firstOrCreate(
                ['name_ar' => $engagement['name_ar']],
                [
                    'name_en' => $engagement['name_en'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
