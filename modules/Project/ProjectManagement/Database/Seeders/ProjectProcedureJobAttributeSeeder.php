<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectManagement\Models\ProjectProcedureJobAttribute;

class ProjectProcedureJobAttributeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Engineer', 'code' => 'engineer'],
            ['name' => 'Consultant', 'code' => 'consultant'],
            ['name' => 'Contractor', 'code' => 'contractor'],
            ['name' => 'Inspector', 'code' => 'inspector'],
        ] as $item) {
            ProjectProcedureJobAttribute::query()->firstOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'is_active' => true,
                ],
            );
        }
    }
}
