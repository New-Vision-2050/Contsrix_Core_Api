<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\RoleAndPermission\Commands\UpdateRoleCommand;
use Ramsey\Uuid\Uuid;
use Modules\RoleAndPermission\Commands\UpdateRoleAndPermissionCommand;
use Modules\RoleAndPermission\Handlers\UpdateRoleAndPermissionHandler;

class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateRoleCommand(): UpdateRoleCommand
    {
        return new UpdateRoleCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
