<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Ramsey\Uuid\Uuid;
use Modules\RoleAndPermission\DTO\CreateRoleAndPermissionDTO;

class CreateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateRoleDTO(): CreateRoleDTO
    {
        return new CreateRoleDTO(
            name: $this->get('name'),
        );
    }
}
