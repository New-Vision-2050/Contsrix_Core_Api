<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserPrivilege\Commands\UpdateUserPrivilegeCommand;
use Modules\UserInfo\UserPrivilege\Handlers\UpdateUserPrivilegeHandler;

class UpdateUserPrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type_privilege'=> 'required|string',
            'type_allowance'=> 'required|string',
            'rate'=> 'required|string',
            'description'=> 'required|string',
        ];
    }

    public function createUpdateUserPrivilegeCommand(): UpdateUserPrivilegeCommand
    {
        return new UpdateUserPrivilegeCommand(
            id: Uuid::fromString($this->route('id')),
            type_privilege: $this->get('type_privilege'),
            type_allowance: $this->get('type_allowance'),
            rate: $this->get('rate'),
            description: $this->get('description'),
        );
    }
}
