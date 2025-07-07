<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\UpdateManagementWithRelationsDTO;
use Ramsey\Uuid\Uuid;

class UpdateManagementWithRelationsRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:source_management_hierarchies,id,type,management',
            'manager_id' => 'nullable|string|exists:users,id',
            'description' => 'nullable|string',
            'job_types' => 'nullable|array',
            'job_types.*' => 'required|string|exists:job_types,id',
            'job_titles' => 'nullable|array',
            'job_titles.*' => 'required|string|exists:job_titles,id',
            'branches' => 'nullable|array',
            'branches.*' => 'required|integer|exists:management_hierarchies,id,type,branch',
            'deputy_manager_ids' => 'nullable|array',
            'deputy_manager_ids.*' => 'required|string|exists:users,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function createUpdateManagementWithRelationsDTO(): UpdateManagementWithRelationsDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        return new UpdateManagementWithRelationsDTO(
            managementId: (int)$this->route('id'),
            name: $this->get('name'),
            parentId: $this->get('parent_id') ? (int)$this->get('parent_id') : null,
            companyId: Uuid::fromString($company->id),
            managerId: $this->get('manager_id') ? Uuid::fromString($this->get('manager_id')) : null,
            description: $this->get('description', ""),
            isActive: $this->get('is_active', 1),
            jobTypes: $this->get('job_types') ?? [],
            jobTitles: $this->get('job_titles') ?? [],
            branches: $this->get('branches') ?? [],
            deputyManagerIds: $this->get('deputy_manager_ids') ?? [],
        );
    }
}
