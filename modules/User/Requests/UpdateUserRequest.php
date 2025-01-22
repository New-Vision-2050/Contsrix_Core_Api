<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\User\Commands\UpdateUserCommand;
use Modules\User\Handlers\UpdateUserHandler;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.Uuid::fromString($this->route('id')),
        ];
    }

    public function createUpdateUserCommand(): UpdateUserCommand
    {
        return new UpdateUserCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            email: $this->get('email'),
        );
    }
}
