<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Ramsey\Uuid\Uuid;

class CreateManagementRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'management_id' => 'nullable|exists:management_hierarchies,id,type,management',
            'branch_id' => 'required|exists:management_hierarchies,id,type,branch',
            'description' => 'required|string',
            'is_active' => 'required|in:1,0',
            "deputy_manager_ids"=>"required|array",
            "deputy_manager_ids.*"=>"required|exists:users,id",
            "reference_user_id"=>"required|exists:users,id",

        ];
    }

    public function createCreateManagementDTO(): CreateManagementDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        return new CreateManagementDTO(
            name: $this->get('name'),
            managementId: $this->get('management_id')?(int)$this->get('management_id'):null,
            branchId: (int)$this->get('branch_id'),
            companyId:Uuid::fromString( $company->id),
            description: $this->get('description'),
            isActive: (int)$this->get('is_active'),
            deputyManagerIds: $this->get('deputy_manager_ids'),
            referenceUserId: Uuid::fromString($this->get('reference_user_id'))
        );

    }
}
