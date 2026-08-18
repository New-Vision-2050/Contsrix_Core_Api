<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\Project\ProjectManagement\Commands\UpdateProjectManagementCommand;
use Modules\Project\ProjectManagement\Enums\ProjectReportCode;
use Modules\Project\ProjectManagement\Handlers\UpdateProjectManagementHandler;

class UpdateProjectManagementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_type_id' => 'required|integer|exists:project_types,id',
            'sub_project_type_id' => 'required|integer|exists:project_types,id',
            'sub_sub_project_type_id' => 'required|integer|exists:project_types,id',
            'name' => 'nullable|string|max:255',
            'manager_id' => 'nullable|uuid|exists:users,id',
            'branch_id' => 'nullable|exists:management_hierarchies,id',
            'project_owner_type' => 'nullable|string|in:company,individual',
            'project_owner_id' => 'nullable|uuid',
            'contract_id' => 'nullable|uuid',
            'contractual_engagement_id' => 'nullable|uuid|exists:contractual_engagements,id',
            'project_tag_id' => 'nullable|uuid|exists:project_tags,id',
            'client_id' => 'nullable',
            'project_classification_id' => 'nullable|uuid',
            'cost_center_branch_id' => 'nullable|exists:management_hierarchies,id',
            'management_id' => 'nullable|exists:management_hierarchies,id',
            'currency_id' => 'nullable|uuid|exists:currencies,id',
            'project_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:-1,0,1',
            'code_report' => ['sometimes', Rule::enum(ProjectReportCode::class)],
        ];
    }

    public function createUpdateProjectManagementCommand(): UpdateProjectManagementCommand
    {
        return new UpdateProjectManagementCommand(
            id: Uuid::fromString($this->route('id')),
            projectTypeId: (int)$this->get('project_type_id'),
            subProjectTypeId:(int) $this->get('sub_project_type_id'),
            subSubProjectTypeId:(int) $this->get('sub_sub_project_type_id'),
            name: $this->get('name'),
            managerId: $this->get('manager_id'),
            branchId:$this->get('branch_id') != null ? (string) $this->get('branch_id') : null,
            projectOwnerType:$this->get('project_owner_type') != null ? (string) $this->get('project_owner_type') : null,
            projectOwnerId:$this->get('project_owner_id') != null ? (string) $this->get('project_owner_id') : null,
            contractId: $this->get('contract_id'),
            contractualEngagementId: $this->get('contractual_engagement_id'),
            projectTagId: $this->get('project_tag_id'),
            clientId: $this->get('client_id'),
            projectClassificationId: $this->get('project_classification_id'),
            costCenterBranchId: $this->get('cost_center_branch_id'),
            managementId:$this->get('management_id') != null ? (string) $this->get('management_id') : null,
            currencyId: $this->get('currency_id'),
            projectValue:(float) $this->get('project_value'),
            status: $this->get('status'),
            codeReport: $this->filled('code_report')
                ? (string) $this->get('code_report')
                : null,
        );
    }
}
