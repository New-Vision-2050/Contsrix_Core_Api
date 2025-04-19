<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TypePrivilege\Commands\UpdateTypePrivilegeCommand;
use Modules\Shared\TypePrivilege\Handlers\UpdateTypePrivilegeHandler;

class UpdateTypePrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTypePrivilegeCommand(): UpdateTypePrivilegeCommand
    {
        return new UpdateTypePrivilegeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
