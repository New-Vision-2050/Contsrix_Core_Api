<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\CloneManagementDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\DTO\UpdateCloneManagementDTO;
use Ramsey\Uuid\Uuid;

class UpdateCloneManagementRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'source_department_id' => 'required|exists:source_management_hierarchies,id',
            'target_parent_id' => 'required_without:target_branch_id|nullable|exists:management_hierarchies,id,type,management',
            "deputy_manager_ids"=>"nullable|array",
            "deputy_manager_ids.*"=>"required|exists:users,id",
            "reference_user_id"=>"nullable|exists:users,id",
            "manager_id"=>"nullable|exists:users,id"

        ];
    }

    public function createCloneManagementDTO(): UpdateCloneManagementDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        return new UpdateCloneManagementDTO(
            id:(int)$this->route('id'),
            taregtId:(int)$this->get('target_parent_id'),
            sourceId:(int)$this->get('source_department_id'),

            companyId:Uuid::fromString( $company->id),

            deputyManagerIds: $this->get('deputy_manager_ids'),
            referenceUserId: $this->get('reference_user_id') ? Uuid::fromString($this->get('reference_user_id')):null,
            managerId:$this->get('manager_id')?Uuid::fromString($this->get('manager_id')):null
        );

    }
}
