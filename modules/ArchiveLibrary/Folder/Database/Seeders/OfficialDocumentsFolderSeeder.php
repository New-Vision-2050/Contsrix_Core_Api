<?php

namespace Modules\ArchiveLibrary\Folder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ArchiveLibrary\Folder\Models\Folder;

class OfficialDocumentsFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            DB::beginTransaction();

            $officialDocumentsUuid = config('folder.official_documents_uuid');
            $officialDocumentsName = config('folder.official_documents_name');

            // Check if folder already exists
            $existingFolder = Folder::find($officialDocumentsUuid);

            if ($existingFolder) {
                Log::info("Official Documents folder already exists with ID: {$officialDocumentsUuid}");
                $this->command->info("✓ Official Documents folder already exists");
                DB::commit();
                return;
            }

            // Create the official documents folder
            $folder = new Folder();
            $folder->id = $officialDocumentsUuid;
            $folder->name = $officialDocumentsName;
            $folder->parent_id = null; // Root level folder
            $folder->access_type = 'public'; // Default to public access
            $folder->company_id = tenant('id')??"560005d6-04b8-53b3-9889-d312648288e3"; // Set company_id for tenant
            $folder->save();

            Log::info("Official Documents folder created successfully with ID: {$officialDocumentsUuid}");
            $this->command->info("✓ Official Documents folder created successfully");
            $this->command->info("  ID: {$officialDocumentsUuid}");
            $this->command->info("  Name: {$officialDocumentsName}");

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create Official Documents folder: " . $e->getMessage());
            $this->command->error("✗ Failed to create Official Documents folder: " . $e->getMessage());
            throw $e;
        }
    }
}
