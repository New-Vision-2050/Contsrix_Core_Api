<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\RoleAndPermission\DTO\CreateRoleAndPermissionDTO;

class CreateRoleAndPermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateRoleAndPermissionDTO(): CreateRoleAndPermissionDTO
    {
        return new CreateRoleAndPermissionDTO(
            name: $this->get('name'),
        );
    }
}
