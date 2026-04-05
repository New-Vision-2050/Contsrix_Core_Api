<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Observers;

use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Illuminate\Support\Facades\DB;

class ProjectManagementObserver
{
    /**
     * Handle the ProjectManagement "creating" event.
     */
    public function creating(ProjectManagement $project): void
    {
        // Generate serial number only if it's not already set
        if (is_null($project->serial_number)) {
            $project->serial_number = $this->generateSerialNumber($project->company_id);
        }
    }

    /**
     * Handle the ProjectManagement "created" event.
     */
    public function created(ProjectManagement $project): void
    {
        // Create a folder for the project
        $this->createProjectFolder($project);
    }

    /**
     * Handle the ProjectManagement "updated" event.
     */
    public function updated(ProjectManagement $project): void
    {
        // Update the folder name if project name changed
        if ($project->isDirty('name')) {
            $this->updateProjectFolder($project);
        }
    }

    /**
     * Generate a unique serial number for the project.
     * Format: PRJ-{company_code}-{number} (e.g., PRJ-COMP1-0001)
     */
    private function generateSerialNumber(string $companyId): string
    {
        // Get the maximum serial number for this company
        $maxSerial = DB::table('projects')
            ->where('company_id', $companyId)
            ->where('serial_number', 'like', 'PRJ-%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(serial_number, \'-\', -1) AS UNSIGNED) DESC')
            ->value('serial_number');

        if ($maxSerial) {
            // Extract numeric part (after last dash) and increment
            $parts = explode('-', $maxSerial);
            $numericPart = (int) end($parts);
            $newNumeric = $numericPart + 1;
        } else {
            $newNumeric = 1;
        }

        // Create a short company code from the company ID (first 4 chars)
        $companyCode = strtoupper(substr($companyId, 0, 4));

        // Format with leading zeros (4 digits)
        return 'PRJ-' . $companyCode . '-' . str_pad((string)$newNumeric, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a folder for the project
     */
    private function createProjectFolder(ProjectManagement $project): void
    {
        try {
            Folder::create([
                "id"=>$project->id,
                'name' => $project->name,
                'project_id' => $project->id,
                'company_id' => $project->company_id,
                'access_type' => 'public',
                'status' => 1,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create folder for project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'project_name' => $project->name,
            ]);
        }
    }

    /**
     * Update the project folder name
     */
    private function updateProjectFolder(ProjectManagement $project): void
    {
        try {
            $folder = Folder::where('project_id', $project->id)->first();

            if ($folder) {
                $folder->update([
                    'name' => $project->name,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update folder for project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'project_name' => $project->name,
            ]);
        }
    }
}
