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
            'type_privilege'=> 'nullable|string',
            'type_allowance'=> 'nullable|string',
            'rate'=> 'nullable|string',
            'description'=> 'nullable|string',
            'privilege_id'=> 'required|string',
            'period' => 'nullable|string',
            'insurance_company'=> 'nullable|string',
            'insurance_number'=> 'nullable|string',
        ];
    }

    public function createCreateUserPrivilegeDTO(): CreateUserPrivilegeDTO
    {
        return new CreateUserPrivilegeDTO(
            company_id:'',
            global_id: '',
            type_privilege: $this->get('type_privilege'),
            type_allowance: $this->get('type_allowance'),
            rate: $this->get('rate'),
            description: $this->get('description'),
            privilege_id:$this->get('privilege_id'),
            period: $this->get('period'),
            insurance_company: $this->get('insurance_company'),
            insurance_number: $this->get('insurance_number'),
        );
    }
}
