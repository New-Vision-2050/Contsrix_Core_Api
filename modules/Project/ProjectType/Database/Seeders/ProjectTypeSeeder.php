<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectType\Models\ProjectType;
use Illuminate\Support\Facades\DB;

class ProjectTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $companyId = tenant("id");

            // Level 1: Root Project Types (is_created = false for seeded data)
            $construction = ProjectType::firstOrCreate(
                [
                    'name' => 'المشاريع الهندسيه',
                    'company_id' => $companyId,
                ],
                [
                    'icon' => 'construction',
                    'parent_id' => null,
                    'is_created' => false,
                    'is_have_schema' => false,
                    'is_active' => true,
                ]
            );

            //Level 2: Second Level Project Types (is_created = true for seeded data)
            $design = ProjectType::firstOrCreate(
                [
                    'name' => 'التصاميم',
                    'company_id' => $companyId,
                ],
                [
                    'icon' => 'construction',
                    'parent_id' => $construction->id,
                    'is_created' => false,
                    'is_have_schema' => false,
                    'is_active' => true,
                ]
            );

            $createdCount = $construction->wasRecentlyCreated ? 1 : 0;

            $this->command->info('ProjectType seeder completed successfully!');
            $this->command->info("Created {$createdCount} new project types (skipped existing for this company).");
        });
    }
}
