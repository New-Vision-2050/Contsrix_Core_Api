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
            'type_privilege'=> 'required|string',
            'type_allowance'=> 'required|string',
            'rate'=> 'required|string',
            'description'=> 'required|string',
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
        );
    }
}
