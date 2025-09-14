<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\DeleteRoleForUserCommand;
use Ramsey\Uuid\Uuid;

class DeleteUserRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Only validates that user_id exists in route, no additional validation needed
        ];
    }

    public function createDeleteRoleCommand(int $role): DeleteRoleForUserCommand
    {
        return new DeleteRoleForUserCommand(
            user_id: Uuid::fromString($this->route('id')),
            role: $role,
        );
    }
}
