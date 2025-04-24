<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Privilege\DTO\CreatePrivilegeDTO;

class CreatePrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreatePrivilegeDTO(): CreatePrivilegeDTO
    {
        return new CreatePrivilegeDTO(
            name: $this->get('name'),
        );
    }
}
