<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\AdminRequest\DTO\CreateAdminRequestDTO;

class CreateAdminRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateAdminRequestDTO(): CreateAdminRequestDTO
    {
        return new CreateAdminRequestDTO(
            name: $this->get('name'),
        );
    }
}
