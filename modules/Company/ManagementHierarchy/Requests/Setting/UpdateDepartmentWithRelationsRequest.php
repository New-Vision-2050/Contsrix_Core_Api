<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\UpdateDepartmentWithRelationsDTO;
use Ramsey\Uuid\Uuid;

class UpdateDepartmentWithRelationsRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        $departmentId = $this->route('id');
        
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:management_hierarchies,id,type,management',
            'managements' => 'nullable|array',
            'managements.*' => 'required|integer|exists:management_hierarchies,id,type,management',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function createUpdateDepartmentWithRelationsDTO(): UpdateDepartmentWithRelationsDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();

        return new UpdateDepartmentWithRelationsDTO(
            departmentId: (int)$this->route('id'),
            name: $this->get('name'),
            parentId: $this->get('parent_id') ? (int)$this->get('parent_id') : null,
            companyId: Uuid::fromString($company->id),
            isActive: $this->get('is_active', 1),
            managements: $this->get('managements') ?? [],
        );
    }
}
