<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Modules\Project\ProjectManagement\Models\ProjectManagement;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Presenters\ProjectDataSettingPresenter;
use Modules\Project\ProjectType\Presenters\AttachmentContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\AttachmentTermsContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\ContractorContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\EmployeeContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\DepartmentContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\AttachmentCycleSettingPresenter;
use Modules\Project\ProjectType\Presenters\ArchiveLibrarySettingPresenter;
use Modules\Project\ProjectType\Presenters\RolesAndPermissionsSettingPresenter;
use Modules\Project\ProjectType\Presenters\ProjectSharingSettingPresenter;

class ProjectManagementPresenter extends AbstractPresenter
{
    private ProjectManagement $projectManagement;

    public function __construct(ProjectManagement $projectManagement)
    {
        $this->projectManagement = $projectManagement;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->projectManagement->id,
            'serial_number' => $this->projectManagement->serial_number,
            'name' => $this->projectManagement->name,
            'project_type_id' => $this->projectManagement->project_type_id,
            'sub_project_type_id' => $this->projectManagement->sub_project_type_id,
            'sub_sub_project_type_id' => $this->projectManagement->sub_sub_project_type_id,
            'manager_id' => $this->projectManagement->manager_id,
            'branch_id' => $this->projectManagement->branch_id,
            'project_owner_type' => $this->projectManagement->project_owner_type,
            'project_owner_id' => $this->projectManagement->project_owner_id,
            'contract_id' => $this->projectManagement->contract_id,
            'client_id' => $this->projectManagement->client_id,
            'project_classification_id' => $this->projectManagement->project_classification_id,
            'cost_center_branch_id' => $this->projectManagement->cost_center_branch_id,
            'management_id' => $this->projectManagement->management_id,
            'currency_id' => $this->projectManagement->currency_id,
            'project_value' => $this->projectManagement->project_value,
            'status' => $this->projectManagement->status,
            'company_id' => $this->projectManagement->company_id,
            'created_at' => $this->projectManagement->created_at?->toDateTimeString(),
            'updated_at' => $this->projectManagement->updated_at?->toDateTimeString(),
        ];

        if (!$isListing) {
            $data['project_type'] = $this->projectManagement->projectType ? [
                'id' => $this->projectManagement->projectType->id,
                'name' => $this->projectManagement->projectType->name,
            ] : null;

            $data['sub_project_type'] = $this->projectManagement->subProjectType ? [
                'id' => $this->projectManagement->subProjectType->id,
                'name' => $this->projectManagement->subProjectType->name,
            ] : null;

            $data['sub_sub_project_type'] = $this->projectManagement->subSubProjectType ? [
                'id' => $this->projectManagement->subSubProjectType->id,
                'name' => $this->projectManagement->subSubProjectType->name,
            ] : null;

            $data['manager'] = $this->projectManagement->manager ? [
                'id' => $this->projectManagement->manager->id,
                'name' => $this->projectManagement->manager->name,
                'email' => $this->projectManagement->manager->email,
            ] : null;

            $data['branch'] = $this->projectManagement->branch ? [
                'id' => $this->projectManagement->branch->id,
                'name' => $this->projectManagement->branch->name,
            ] : null;


            $data['project_owner'] = $this->projectManagement->projectOwner ? [
                'id' => $this->projectManagement->projectOwner->id,
                'name' => $this->projectManagement->projectOwner->name ?? null,
                'type' => $this->projectManagement->project_owner_type,
            ] : null;

            $data['project_classification'] = $this->projectManagement->project_classification_id ? [
                'id' => $this->projectManagement->project_classification_id,
            ] : null;

            $data['company'] = $this->projectManagement->company ? [
                'id' => $this->projectManagement->company->id,
                'name' => $this->projectManagement->company->name,
            ] : null;

            $data['client'] = $this->projectManagement->client ? [
                'id' => $this->projectManagement->client->id,
                'name' => $this->projectManagement->client->name,
            ] : null;

            $data['cost_center_branch'] = $this->projectManagement->costCenterBranch ? [
                'id' => $this->projectManagement->costCenterBranch->id,
                'name' => $this->projectManagement->costCenterBranch->name,
            ] : null;

            $data['management'] = $this->projectManagement->management ? [
                'id' => $this->projectManagement->management->id,
                'name' => $this->projectManagement->management->name,
            ] : null;

            $data['currency'] = $this->projectManagement->currency ? [
                'id' => $this->projectManagement->currency->id,
                'name' => $this->projectManagement->currency->name,
                'code' => $this->projectManagement->currency->code ?? null,
            ] : null;

            // Add employees assigned to this project
            $data['employees'] = [];
            if ($this->projectManagement->relationLoaded('employees')) {
                $data['employees'] = $this->projectManagement->employees->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'assigned_at' => $employee->pivot?->assigned_at,
                    ];
                })->toArray();
            }

            // Check if project is shared and get allowed schemas
            $allowedSchemas = null;
            $isShared = false;

            if ($this->projectManagement->company_id !== tenant('id')) {
                // This is a shared project, get the share record
                $isShared = true;
                if ($this->projectManagement->relationLoaded('shares')) {
                    $share = $this->projectManagement->shares->first(function ($share) {
                        return $share->shared_with_company_id === tenant('id') && $share->status === 'accepted';
                    });

                    if ($share && $share->schema_ids) {
                        $allowedSchemas = $share->schema_ids;
                    }
                }
            }

            $data['is_shared'] = $isShared;
            $data['allowed_schemas'] = $allowedSchemas;

            // Schema ID mapping
            $schemaMapping = [
                1 => 'project_data_setting',
                2 => 'attachment_contract_setting',
                3 => 'attachment_terms_contract_setting',
                4 => 'contractor_contract_setting',
                5 => 'employee_contract_setting',
                6 => 'department_contract_setting',
                7 => 'attachment_cycle_setting',
                8 => 'archive_library_setting',
            ];

            // Add contract settings from subSubProjectType wrapped in permissions array
            $permissions = [];

            if ($this->projectManagement->subSubProjectType) {
                // Schema 1: Project Data Setting
                if ($this->shouldIncludeSchema(1, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('projectDataSetting') &&
                    $this->projectManagement->subSubProjectType->projectDataSetting) {
                    $permissions['project_data_setting'] = (new ProjectDataSettingPresenter($this->projectManagement->subSubProjectType->projectDataSetting))->getData();
                }

                // Schema 2: Attachment Contract Setting
                if ($this->shouldIncludeSchema(2, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('attachmentContractSetting') &&
                    $this->projectManagement->subSubProjectType->attachmentContractSetting) {
                    $permissions['attachment_contract_setting'] = (new AttachmentContractSettingPresenter($this->projectManagement->subSubProjectType->attachmentContractSetting))->getData();
                }

                // Schema 3: Attachment Terms Contract Setting
                if ($this->shouldIncludeSchema(3, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('attachmentTermsContractSetting') &&
                    $this->projectManagement->subSubProjectType->attachmentTermsContractSetting) {
                    $permissions['attachment_terms_contract_setting'] = (new AttachmentTermsContractSettingPresenter($this->projectManagement->subSubProjectType->attachmentTermsContractSetting))->getData();
                }

                // Schema 4: Contractor Contract Setting
                if ($this->shouldIncludeSchema(4, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('contractorContractSetting') &&
                    $this->projectManagement->subSubProjectType->contractorContractSetting) {
                    $permissions['contractor_contract_setting'] = (new ContractorContractSettingPresenter($this->projectManagement->subSubProjectType->contractorContractSetting))->getData();
                }

                // Schema 5: Employee Contract Setting
                if ($this->shouldIncludeSchema(5, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('employeeContractSetting') &&
                    $this->projectManagement->subSubProjectType->employeeContractSetting) {
                    $permissions['employee_contract_setting'] = (new EmployeeContractSettingPresenter($this->projectManagement->subSubProjectType->employeeContractSetting))->getData();
                }

                // Schema 6: Department Contract Setting
                if ($this->shouldIncludeSchema(6, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('departmentContractSetting') &&
                    $this->projectManagement->subSubProjectType->departmentContractSetting) {
                    $permissions['department_contract_setting'] = (new DepartmentContractSettingPresenter($this->projectManagement->subSubProjectType->departmentContractSetting))->getData();
                }

                // Schema 7: Attachment Cycle Setting
                if ($this->shouldIncludeSchema(7, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('attachmentCycleSetting') &&
                    $this->projectManagement->subSubProjectType->attachmentCycleSetting) {
                    $permissions['attachment_cycle_setting'] = (new AttachmentCycleSettingPresenter($this->projectManagement->subSubProjectType->attachmentCycleSetting))->getData();
                }

                // Schema 8: Archive Library Setting
                if ($this->shouldIncludeSchema(8, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('archiveLibrarySetting') &&
                    $this->projectManagement->subSubProjectType->archiveLibrarySetting) {
                    $permissions['archive_library_setting'] = (new ArchiveLibrarySettingPresenter($this->projectManagement->subSubProjectType->archiveLibrarySetting))->getData();
                }

                // Schema 10: Roles and Permissions Setting
                if ($this->shouldIncludeSchema(10, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('rolesAndPermissionsSetting') &&
                    $this->projectManagement->subSubProjectType->rolesAndPermissionsSetting) {
                    $permissions['roles_and_permissions_setting'] = (new RolesAndPermissionsSettingPresenter($this->projectManagement->subSubProjectType->rolesAndPermissionsSetting))->getData();
                }

                // Schema 11: Project Sharing Setting
                if ($this->shouldIncludeSchema(11, $allowedSchemas) &&
                    $this->projectManagement->subSubProjectType->relationLoaded('projectSharingSetting') &&
                    $this->projectManagement->subSubProjectType->projectSharingSetting) {
                    $permissions['project_sharing_setting'] = (new ProjectSharingSettingPresenter($this->projectManagement->subSubProjectType->projectSharingSetting))->getData();
                }
            }
            $data['permissions'] = $permissions;
        } else {
            $data['project_type_name'] = $this->projectManagement->projectType?->name;
            $data['sub_project_type_name'] = $this->projectManagement->subProjectType?->name;
            $data['sub_sub_project_type_name'] = $this->projectManagement->subSubProjectType?->name;
            $data['manager_name'] = $this->projectManagement->manager?->name;
            $data['branch_name'] = $this->projectManagement->branch?->name;
            $data['client_name'] = $this->projectManagement->client?->name;
            $data['project_owner_name'] = $this->projectManagement->projectOwner?->name;
            $data['currency_name'] = $this->projectManagement->currency?->name;
            $data['currency_code'] = $this->projectManagement->currency?->code;
            $data['cost_center_branch_name'] = $this->projectManagement->costCenterBranch?->name;
            $data['management_name'] = $this->projectManagement->management?->name;
        }

        return $data;
    }

    /**
     * Check if a schema should be included based on allowed schemas
     * If allowedSchemas is null, all schemas are allowed (owner company)
     * Otherwise, only schemas in the allowedSchemas array are included
     */
    private function shouldIncludeSchema(int $schemaId, ?array $allowedSchemas): bool
    {
        if ($allowedSchemas === null) {
            return true; // Owner company sees everything
        }

        return in_array($schemaId, $allowedSchemas);
    }
}
