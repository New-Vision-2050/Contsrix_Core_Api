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
            'type_privilege'=> 'nullable|string',
            'type_allowance'=> 'nullable|string',
            'charge_amount'=> 'nullable|string',
            'description'=> 'nullable|string',
            'period' => 'nullable|string',
        ];
    }

    public function createUpdateUserPrivilegeCommand(): UpdateUserPrivilegeCommand
    {
        return new UpdateUserPrivilegeCommand(
            id: Uuid::fromString($this->route('id')),
            type_privilege: $this->get('type_privilege'),
            type_allowance: $this->get('type_allowance'),
            charge_amount: $this->get('charge_amount'),
            description: $this->get('description'),
            period: $this->get('period'),
        );
    }
}
