<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateDepartmentDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Ramsey\Uuid\Uuid;

class CreateDepartmentRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'department_id' => 'nullable|exists:management_hierarchies,id,type,department',
            'management_id' => 'required|exists:management_hierarchies,id,type,management',
            'description' => 'required|string',
            'is_active' => 'required|in:1,0',

        ];
    }

    public function createCreateDepartmentDTO(): CreateDepartmentDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        return new CreateDepartmentDTO(
            name: $this->get('name'),
            managementId: (int)$this->get('management_id'),
            departmentId: $this->get('department_id')?(int)$this->get('department_id'):null,
            companyId: Uuid::fromString( $company->id),
            description: $this->get('description'),
            isActive: (int)$this->get('is_active')
        );

    }
}
