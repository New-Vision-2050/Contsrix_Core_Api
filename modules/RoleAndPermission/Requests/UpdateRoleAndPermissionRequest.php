<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\RoleAndPermission\Commands\UpdateRoleAndPermissionCommand;
use Modules\RoleAndPermission\Handlers\UpdateRoleAndPermissionHandler;

class UpdateRoleAndPermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateRoleAndPermissionCommand(): UpdateRoleAndPermissionCommand
    {
        return new UpdateRoleAndPermissionCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
