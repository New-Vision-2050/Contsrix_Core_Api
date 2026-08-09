<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectTag;

class ProjectTagSeeder extends Seeder
{
    public function run(): void
    {
        ProjectTag::query()->updateOrCreate(
            ['code' => 'TECHSITE'],
            [
                'name' => [
                    'ar' => 'TechSite',
                    'en' => 'TechSite',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }
}
