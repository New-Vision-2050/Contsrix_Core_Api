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
            'rate'=> 'nullable|string',
            'description'=> 'nullable|string',
            'period' => 'nullable|string',
            'insurance_company'=> 'nullable|string',
            'insurance_number'=> 'nullable|string',
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
            period: $this->get('period'),
            insurance_company: $this->get('insurance_company'),
            insurance_number: $this->get('insurance_number'),
        );
    }
}
