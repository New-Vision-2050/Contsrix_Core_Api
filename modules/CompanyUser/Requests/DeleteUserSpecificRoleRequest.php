<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CompanyUser\Commands\DeleteRoleForUserCommand;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Ramsey\Uuid\Uuid;

class DeleteUserSpecificRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "role_id" => ["required", Rule::enum(CompanyUserRole::class)],
        ];
    }

    public function createDeleteRoleCommand(): DeleteRoleForUserCommand
    {
        return new DeleteRoleForUserCommand(
            user_id: Uuid::fromString($this->route('user_id')),
            role: (int) $this->get('role_id'),
        );
    }
}
