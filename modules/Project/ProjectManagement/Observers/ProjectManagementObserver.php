<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Observers;

use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectPermission;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Set created_by_user_id if not already set
        if (is_null($project->created_by_user_id) && auth()->check()) {
            $project->created_by_user_id = auth()->id();
        }
    }

    /**
     * Handle the ProjectManagement "created" event.
     */
    public function created(ProjectManagement $project): void
    {
        // Create a folder for the project
        $this->createProjectFolder($project);

        // Create default admin role and assign manager/creator
        $this->createProjectAdminRole($project);
    }

    /**
     * Handle the ProjectManagement "updated" event.
     */
    public function updated(ProjectManagement $project): void
    {
        // Update the folder name if project name changed
        if ($project->wasChanged('name')) {
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
            // Use DB insert to bypass UUID trait auto-generation
            DB::table('folders')->insert([
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->id,
                'company_id' => $project->company_id,
                'access_type' => 'public',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create folder for project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

    /**
     * Create default admin role for project and assign manager/creator
     */
    private function createProjectAdminRole(ProjectManagement $project): void
    {
        try {
            DB::transaction(function () use ($project) {
                // Create default "Project Admin" role
                $adminRole = ProjectRole::create([
                    'project_id' => $project->id,
                    'name' => 'Project Admin',
                    'slug' => 'project-admin',
                    'description' => 'Full access to project resources',
                    'is_default' => true,
                    'is_active' => true,
                ]);

                // Assign ALL permissions to admin role
                $allPermissions = ProjectPermission::where('is_active', true)->pluck('id');
                if ($allPermissions->isNotEmpty()) {
                    $adminRole->permissions()->sync($allPermissions);
                }

                // Add manager as admin (if exists)
                if ($project->manager_id) {
                    ProjectEmployee::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'user_id' => $project->manager_id,
                        ],
                        [
                            'company_id' => $project->company_id,
                            'project_role_id' => $adminRole->id,
                            'assigned_by_user_id' => $project->created_by_user_id,
                            'assigned_at' => now(),
                        ]
                    );
                }

                // Add creator as admin (if different from manager)
                if ($project->created_by_user_id && $project->created_by_user_id !== $project->manager_id) {
                    ProjectEmployee::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'user_id' => $project->created_by_user_id,
                        ],
                        [
                            'company_id' => $project->company_id,
                            'project_role_id' => $adminRole->id,
                            'assigned_by_user_id' => $project->created_by_user_id,
                            'assigned_at' => now(),
                        ]
                    );
                }

                Log::info('Project admin role created successfully', [
                    'project_id' => $project->id,
                    'admin_role_id' => $adminRole->id,
                    'permissions_count' => $allPermissions->count(),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to create admin role for project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
