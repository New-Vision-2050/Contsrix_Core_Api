<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectType\Models\Schema;
use Modules\Project\ProjectType\Models\ProjectType;
use Illuminate\Support\Facades\DB;

class SchemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $schemas = [
//                1 => 'بيانات المشروع',
//                2 => 'بنود المشروع',
                3 => 'المرفقات',
//                4 => 'المقاولين',
                5 => 'المعنيين',
//                6 => 'اوامر العمل',
//                7 => 'المالية',
//                8 => 'اداره العقد',
                9=>'دورة الوثائق',
                10 => 'الصلاحيات و الادوار',
                11 => 'مشاركة المشروع'
            ];

            $createdCount = 0;
            $schemaIds = [];

            foreach ($schemas as $id => $schemaName) {
                $schema = Schema::firstOrCreate(
                    ['id' => $id],
                    ['id' => $id, 'name' => $schemaName]
                );

                if ($schema->name !== $schemaName) {
                    $schema->update(['name' => $schemaName]);
                }

                $schemaIds[] = $schema->id;

                if ($schema->wasRecentlyCreated) {
                    $createdCount++;
                }
            }

            // Attach all schemas to التصاميم project type for current tenant
            $companyId = tenant("id");
            if ($companyId) {
                $designProjectType = ProjectType::where('name', 'التصاميم')
                    ->where('company_id', $companyId)
                    ->first();

                if ($designProjectType) {
                    // Sync schemas (will not create duplicates)
                    $designProjectType->schemas()->sync($schemaIds);

                    // Update is_have_schema flag
                    $designProjectType->update(['is_have_schema' => true]);

                    $this->command->info("Attached " . count($schemaIds) . " schemas to 'التصاميم' project type.");
                }
            }

            $this->command->info('Schema seeder completed successfully!');
            $this->command->info("Created {$createdCount} new schema types (skipped " . (count($schemas) - $createdCount) . " existing).");
        });
    }
}
