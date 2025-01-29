<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\RoleAndPermission\Commands\UpdatePermissionCommand;
use Modules\RoleAndPermission\Commands\UpdateRoleCommand;
use Nwidart\Modules\Commands\UpdateCommand;
use Ramsey\Uuid\Uuid;
use Modules\RoleAndPermission\Commands\UpdateRoleAndPermissionCommand;
use Modules\RoleAndPermission\Handlers\UpdateRoleAndPermissionHandler;

class UpdatePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdatePermissionCommand(): UpdatePermissionCommand
    {
        return new UpdatePermissionCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
