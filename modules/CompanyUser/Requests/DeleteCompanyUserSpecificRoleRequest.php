<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CompanyUser\Commands\DeleteRoleForCompanyUserCommand;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Ramsey\Uuid\Uuid;

class DeleteCompanyUserSpecificRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "role_id" => ["required",Rule::enum(CompanyUserRole::class)],
            "company_id"=>"required|exists:companies,id"
        ];
    }


    public function createDeleteRoleCommand(): DeleteRoleForCompanyUserCommand
    {
        return new DeleteRoleForCompanyUserCommand(
            id: Uuid::fromString($this->route('id')),
            company_id: Uuid::fromString($this->get('company_id')),
            role:(int) $this->get('role_id'),
        );
    }
}
