<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\CreateDepartmentWithRelationsDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementWithRelationsDTO;
use Ramsey\Uuid\Uuid;

class CreateDepartmentWithRelationsRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:management_hierarchies,id,type,management',
            'managements' => 'nullable|array',
            'managements.*' => 'required|integer|exists:management_hierarchies,id,type,management',
        ];
    }

    public function createCreateDepartmentWithRelationsDTO(): CreateDepartmentWithRelationsDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        return new CreateDepartmentWithRelationsDTO(
            name: $this->get('name'),
            parentId: $this->get('parent_id') ? (int)$this->get('parent_id') : null,
            companyId: Uuid::fromString($company->id),
            isActive: 1,
            managements: $this->get('branches') ?? [],
        );
    }
}
