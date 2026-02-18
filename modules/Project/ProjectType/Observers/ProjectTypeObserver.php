<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Observers;

use Modules\Project\ProjectType\Models\ProjectType;
use Modules\Project\ProjectType\Models\ProjectDataSetting;
use Modules\Project\ProjectType\Models\AttachmentContractSetting;
use Modules\Project\ProjectType\Models\AttachmentTermsContractSetting;
use Modules\Project\ProjectType\Models\ContractorContractSetting;
use Modules\Project\ProjectType\Models\EmployeeContractSetting;
use Modules\Project\ProjectType\Models\DepartmentContractSetting;

class ProjectTypeObserver
{
    /**
     * Handle the ProjectType "created" event.
     * Automatically create settings when a third-level project type is created.
     * Third level = parent is second level (parent has a parent that is root with schema)
     */
    public function created(ProjectType $projectType): void
    {
        // Check if this is a third-level project type
        if ($this->isThirdLevel($projectType)) {
            // Create ProjectDataSetting
            ProjectDataSetting::create([
                'project_type_id' => $projectType->id,
                'is_reference_number' => 1,
                'is_name_project' => 1,
                'is_client' => 1,
                'is_responsible_engineer' => 1,
                'is_number_contract' => 1,
                'is_central_cost' => 1,
                'is_project_value' => 1,
                'is_start_date' => 1,
                'is_achievement_percentage' => 1,
            ]);

            // Create AttachmentContractSetting
            AttachmentContractSetting::create([
                'project_type_id' => $projectType->id,
                'is_name' => 1,
                'is_type' => 1,
                'is_size' => 1,
                'is_creator' => 1,
                'is_create_date' => 1,
                'is_downloadable' => 1,
            ]);

            // Create AttachmentTermsContractSetting
            AttachmentTermsContractSetting::create([
                'project_type_id' => $projectType->id,
                'is_name' => 1,
                'is_type' => 1,
                'is_size' => 1,
                'is_creator' => 1,
                'is_create_date' => 1,
                'is_downloadable' => 1,
            ]);

            // Create ContractorContractSetting
            ContractorContractSetting::create([
                'project_type_id' => $projectType->id,
                'is_all_data_visible' => 1,
            ]);

            // Create EmployeeContractSetting
            EmployeeContractSetting::create([
                'project_type_id' => $projectType->id,
                'is_all_data_visible' => 1,
            ]);

            // Create DepartmentContractSetting
            DepartmentContractSetting::create([
                'project_type_id' => $projectType->id,
                'is_all_data_visible' => 1,
            ]);
        }
    }

    /**
     * Check if the project type is third level
     * Third level = has parent_id AND parent is second level (has schema and parent is root)
     */
    private function isThirdLevel(ProjectType $projectType): bool
    {
        // Must have a parent
        if (!$projectType->parent_id) {
            return false;
        }

        // Load parent with its parent
        $parent = ProjectType::with('parent')
            ->find($projectType->parent_id);

        if (!$parent) {
            return false;
        }

        // Parent must have a parent (not root) and must have schema
        if (!$parent->parent_id || !$parent->is_have_schema) {
            return false;
        }

        // Parent's parent must be root (no parent_id)
        $grandParent = $parent->parent;
        if (!$grandParent || $grandParent->parent_id !== null) {
            return false;
        }

        return true;
    }
}
