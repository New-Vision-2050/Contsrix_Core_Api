<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Commands\AssignRoleForUserCommand;
use Ramsey\Uuid\Uuid;
use Modules\User\Commands\UpdateUserCommand;
use Modules\User\Handlers\UpdateUserHandler;

class AssignRolesForUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'roles' => 'required|array',
            'roles.*' => 'required|exists:roles,name',
        ];
    }

    public function createAssignRoleForUserCommand(): AssignRoleForUserCommand
    {
        return new AssignRoleForUserCommand(
            id: Uuid::fromString($this->route('id')),
            roles: $this->get("roles")
        );
    }
}
