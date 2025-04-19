<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\UserInfo\UserStatus\Commands\UpdatePasswordCommand;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserStatus\Commands\UpdateUserStatusCommand;
use Modules\UserInfo\UserStatus\Handlers\UpdateUserStatusHandler;

class UpdatePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => 'nullable',
            'type'=>'required|in:automatic,manual'
        ];
    }

    public function createUpdateUserStatusCommand(): UpdatePasswordCommand
    {
        return new UpdatePasswordCommand(
            id: Uuid::fromString($this->route('id')),
            password: $this->get('password'),
            type: $this->get('type')
        );
    }

}
