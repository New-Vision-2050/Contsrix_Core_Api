<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\DTO\AssignUsersToManagementHierarchyDTO;

class AssignUsersToManagementHierarchyRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'branch_id' => 'required|integer|exists:management_hierarchies,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|string|uuid|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => __('validation.user_access.branch_id_required'),
            'branch_id.integer' => __('validation.user_access.branch_id_integer'),
            'branch_id.exists' => __('validation.user_access.branch_id_exists'),

            'user_ids.required' => __('validation.user_access.user_ids_required'),
            'user_ids.array' => __('validation.user_access.user_ids_array'),
            'user_ids.min' => __('validation.user_access.user_ids_min'),

            'user_ids.*.required' => __('validation.user_access.user_id_required'),
            'user_ids.*.string' => __('validation.user_access.user_id_string'),
            'user_ids.*.uuid' => __('validation.user_access.user_id_uuid'),
            'user_ids.*.exists' => __('validation.user_access.user_id_exists'),
        ];
    }

    public function createAssignUsersToManagementHierarchyDTO(): AssignUsersToManagementHierarchyDTO
    {
        return new AssignUsersToManagementHierarchyDTO(
            branchId: (int) $this->get('branch_id'),
            userIds: $this->get('user_ids')
        );
    }

    public function getBranchId(): int
    {
        return (int) $this->get('branch_id');
    }

    public function getUserIds(): array
    {
        return $this->get('user_ids');
    }
}
