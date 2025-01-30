<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\RoleAndPermission\Commands\AssignPermissionToRoleCommand;
use Modules\RoleAndPermission\Commands\UpdateRoleCommand;
use Ramsey\Uuid\Uuid;
use Modules\RoleAndPermission\Commands\UpdateRoleAndPermissionCommand;
use Modules\RoleAndPermission\Handlers\UpdateRoleAndPermissionHandler;

class AssignPermissionToRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "permissions"=>"required|array",
            "permissions.*"=>"exists:permissions,name",
        ];
    }

    public function createAssignPermissionToRoleCommand(): AssignPermissionToRoleCommand
    {
        return new AssignPermissionToRoleCommand(
            id: Uuid::fromString($this->route('id')),
            permissions: $this->get('permissions'),
        );
    }
}
