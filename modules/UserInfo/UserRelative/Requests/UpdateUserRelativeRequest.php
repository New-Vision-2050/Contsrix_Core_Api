<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserRelative\Commands\UpdateUserRelativeCommand;
use Modules\UserInfo\UserRelative\Handlers\UpdateUserRelativeHandler;

class UpdateUserRelativeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateUserRelativeCommand(): UpdateUserRelativeCommand
    {
        return new UpdateUserRelativeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
