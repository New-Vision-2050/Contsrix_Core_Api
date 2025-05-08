<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\Commands\MakeBranchMainCommand;
use Modules\Company\ManagementHierarchy\DTO\CreateBranchDTO;
use Modules\Company\ManagementHierarchy\DTO\CreateManagementHierarchyDTO;
use Ramsey\Uuid\Uuid;

class MakeBranchMainRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [

            "branch_id" => "required|exists:management_hierarchies,id",
        ];
    }

    public function createMakeBranchMainCommand(): MakeBranchMainCommand
    {

        return new MakeBranchMainCommand(
            id: (int)$this->route('id'),
            branchId: (int)$this->branch_id,
        );
    }
}
