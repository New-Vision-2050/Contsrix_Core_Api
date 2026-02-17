<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectType\Models\Schema;
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
                'بيانات المشروع',      // Project Details
                'المعلومات المالية',   // Financial Information
                'أطراف العمل',         // Work Parties
                'الكتاب',              // Books/Documents
                'المقاولون',           // Contractors
                'المستشارون',          // Consultants
                'الموردون',            // Suppliers
                'المراقبون',           // Supervisors
            ];

            $createdCount = 0;
            foreach ($schemas as $schemaName) {
                $schema = Schema::firstOrCreate(
                    ['name' => $schemaName],
                    ['name' => $schemaName]
                );
                
                if ($schema->wasRecentlyCreated) {
                    $createdCount++;
                }
            }

            $this->command->info('Schema seeder completed successfully!');
            $this->command->info("Created {$createdCount} new schema types (skipped " . (count($schemas) - $createdCount) . " existing).");
        });
    }
}
