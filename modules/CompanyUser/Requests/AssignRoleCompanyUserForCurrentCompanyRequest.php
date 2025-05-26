<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\AssignRoleCompanyUserCommand;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class AssignRoleCompanyUserForCurrentCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [

            'role' => 'required',

            "branch_ids" => "nullable|array",
            "branch_ids.*" => "exists:management_hierarchies,id,type,branch"
        ];
    }

    public function createAssignCompanyUserForCurrentCompanyCommand(): AssignRoleCompanyUserCommand
    {
        return new AssignRoleCompanyUserCommand(
            id: Uuid::fromString($this->route('id')),
            company_id: Uuid::fromString(tenant("id")),
            role: (int)$this->get('role'),

            branch_ids: $this->get('branch_ids') ,
        );
    }
}
