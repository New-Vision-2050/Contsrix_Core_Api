<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Privilege\Commands\UpdatePrivilegeCommand;
use Modules\Shared\Privilege\Handlers\UpdatePrivilegeHandler;

class UpdatePrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdatePrivilegeCommand(): UpdatePrivilegeCommand
    {
        return new UpdatePrivilegeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
