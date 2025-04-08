<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\AssignRoleCompanyUserCommand;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class AssignRoleCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [

            'role' => 'nullable',
            'company_id' => 'required|exists:companies,id',
        ];
    }

    public function createAssignCompanyUserCommand(): AssignRoleCompanyUserCommand
    {
        return new AssignRoleCompanyUserCommand(
            id: Uuid::fromString($this->route('id')),
            company_id: Uuid::fromString($this->get('company_id')),
            role: $this->get('role')?? 1,
        );
    }
}
