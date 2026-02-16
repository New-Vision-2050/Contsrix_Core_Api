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
            'type_privilege_id'=> 'nullable|string',
            'type_allowance_code'=> 'nullable|string',
            'charge_amount'=> 'nullable|string',
            'description'=> 'nullable|string',
            'period_id' => 'nullable|string',
            'medical_insurance_id' => 'nullable|uuid|exists:medical_insurances,id',
        ];
    }

    public function createUpdateUserPrivilegeCommand(): UpdateUserPrivilegeCommand
    {
        return new UpdateUserPrivilegeCommand(
            id: Uuid::fromString($this->route('id')),
            type_privilege_id: $this->get('type_privilege_id'),
            type_allowance_code: $this->get('type_allowance_code'),
            charge_amount: $this->get('charge_amount'),
            description: $this->get('description'),
            period_id: $this->get('period_id'),
            medical_insurance_id: $this->get('medical_insurance_id'),
        );
    }
}
