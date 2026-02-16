<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserPrivilege\DTO\CreateUserPrivilegeDTO;

class CreateUserPrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id'=> 'required|string',
            'type_privilege_id'=> 'required|string',
            'type_allowance_code'=> 'required|string',
            'charge_amount'=> 'nullable|string',
            'description'=> 'nullable|string',
            'privilege_id'=> 'required|string',
            'period_id' => 'nullable|string',
            'medical_insurance_id' => 'nullable|uuid|exists:medical_insurances,id',
        ];
    }

    public function createCreateUserPrivilegeDTO(): CreateUserPrivilegeDTO
    {
        return new CreateUserPrivilegeDTO(
            company_id:'',
            global_id: '',
            type_privilege_id: $this->get('type_privilege_id'),
            type_allowance_code: $this->get('type_allowance_code'),
            charge_amount: $this->get('charge_amount'),
            description: $this->get('description'),
            privilege_id:$this->get('privilege_id'),
            period_id: $this->get('period_id'),
            medical_insurance_id: $this->get('medical_insurance_id'),
        );
    }
}
