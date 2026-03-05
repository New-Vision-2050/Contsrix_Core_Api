<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use Modules\Project\ProjectType\Models\ProjectType;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Presenters\ProjectDataSettingPresenter;
use Modules\Project\ProjectType\Presenters\AttachmentContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\AttachmentTermsContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\ContractorContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\EmployeeContractSettingPresenter;
use Modules\Project\ProjectType\Presenters\DepartmentContractSettingPresenter;

class ProjectTypePresenter extends AbstractPresenter
{
    private ProjectType $projectType;

    public function __construct(ProjectType $projectType)
    {
        $this->projectType = $projectType;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->projectType->id,
            'name' => $this->projectType->name,
            'icon' => $this->projectType->icon,
            'parent_id' => $this->projectType->parent_id,
            'is_created' => $this->projectType->is_created,
            'is_have_schema' => $this->projectType->is_have_schema,
            'is_active' => $this->projectType->is_active,
            'path' => $this->projectType->path,
            "reference_project_type_id"=>$this->projectType->reference_project_type_id,
        ];
        if ($this->projectType->is_have_schema && $this->projectType->relationLoaded('schemas')) {
            $data['schemas'] = $this->projectType->schemas->map(function ($schema) {
                return [
                    'id' => $schema->id,
                    'name' => $schema->name,

                ];
            })->toArray();
        }


        if ($this->projectType->relationLoaded('referenceProjectType')) {
            $data['reference_project_type'] = $this->projectType->referenceProjectType;
        }


        if (!$isListing) {
            $data['parent'] = $this->projectType->parent ? [
                'id' => $this->projectType->parent->id,
                'name' => $this->projectType->parent->name,
            ] : null;

            $data['children'] = $this->projectType->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'icon' => $child->icon,
                    'is_have_schema' => $child->is_have_schema,
                ];
            })->toArray();


            // Add contract settings wrapped in permissions array
            $permissions = [];
            if ($this->projectType->relationLoaded('projectDataSetting') && $this->projectType->projectDataSetting) {
                $permissions['project_data_setting'] = (new ProjectDataSettingPresenter($this->projectType->projectDataSetting))->getData();
            }

            if ($this->projectType->relationLoaded('attachmentContractSetting') && $this->projectType->attachmentContractSetting) {
                $permissions['attachment_contract_setting'] = (new AttachmentContractSettingPresenter($this->projectType->attachmentContractSetting))->getData();
            }

            if ($this->projectType->relationLoaded('attachmentTermsContractSetting') && $this->projectType->attachmentTermsContractSetting) {
                $permissions['attachment_terms_contract_setting'] = (new AttachmentTermsContractSettingPresenter($this->projectType->attachmentTermsContractSetting))->getData();
            }

            if ($this->projectType->relationLoaded('contractorContractSetting') && $this->projectType->contractorContractSetting) {
                $permissions['contractor_contract_setting'] = (new ContractorContractSettingPresenter($this->projectType->contractorContractSetting))->getData();
            }

            if ($this->projectType->relationLoaded('employeeContractSetting') && $this->projectType->employeeContractSetting) {
                $permissions['employee_contract_setting'] = (new EmployeeContractSettingPresenter($this->projectType->employeeContractSetting))->getData();
            }

            if ($this->projectType->relationLoaded('departmentContractSetting') && $this->projectType->departmentContractSetting) {
                $permissions['department_contract_setting'] = (new DepartmentContractSettingPresenter($this->projectType->departmentContractSetting))->getData();
            }

            $data['permissions'] = $permissions;
        }

        return $data;
    }
}
