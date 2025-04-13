<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserRelative\DTO\CreateUserRelativeDTO;

class CreateUserRelativeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateUserRelativeDTO(): CreateUserRelativeDTO
    {
        return new CreateUserRelativeDTO(
            name: $this->get('name'),
        );
    }
}
