<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\RoleAndPermission\Commands\UpdateRoleCommand;
use Ramsey\Uuid\Uuid;


class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        $tenant = tenant();
        return [
            'name' => ["required", "string", Rule::unique('roles', 'name')->where('company_id', tenant('id'))->whereNot("id",$this->route('id'))],
            'permissions' => 'nullable|array',
            'permissions.*' => 'required|exists:permissions,name',
        ];
    }

    public function createUpdateRoleCommand(): UpdateRoleCommand
    {
        return new UpdateRoleCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            permissions: $this->get('permissions'),
        );
    }
}
