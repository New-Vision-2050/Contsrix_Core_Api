<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TypePrivilege\DTO\CreateTypePrivilegeDTO;

class CreateTypePrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTypePrivilegeDTO(): CreateTypePrivilegeDTO
    {
        return new CreateTypePrivilegeDTO(
            name: $this->get('name'),
        );
    }
}
