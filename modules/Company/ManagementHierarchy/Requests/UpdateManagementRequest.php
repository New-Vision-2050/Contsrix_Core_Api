<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Commands\UpdateManagementCommand;
use Ramsey\Uuid\Uuid;

class UpdateManagementRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'branch_id' => 'required|exists:management_hierarchies,id,type,branch',
            'management_id' => 'required|exists:management_hierarchies,id,type,management',
            'description' => 'required|string',
            'is_active' => 'required|in:1,0',
            "deputy_manager_ids" => "nullable|array",
            "deputy_manager_ids.*" => "required|exists:users,id",
            "reference_user_id" => "nullable|exists:users,id",
            "manager_id" => "nullable|exists:users,id"
        ];
    }

    public function createUpdateManagementCommand(): UpdateManagementCommand
    {
        $company = tenant();
        return new UpdateManagementCommand(
            id: (int)$this->route('id'),
            name: $this->get('name'),
            branchId: (int)$this->get('branch_id'),
            managementId: (int)$this->get('management_id'),
            companyId: Uuid::fromString($company->id),
            description: $this->get('description'),
            isActive: (int)$this->get('is_active'),
            deputyManagerIds: $this->get('deputy_manager_ids'),
            referenceUserId: $this->get("reference_user_id")?Uuid::fromString($this->get('reference_user_id')):null,
            managerId: $this->get("manager_id")?Uuid::fromString($this->get('manager_id')):null
        );
    }
}
