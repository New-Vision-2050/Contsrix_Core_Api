<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserRelative\DTO\CreateUserRelativeDTO;

class CreateUserRelativeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'company_id',
            'global_id',
            'marital_status',
            'relationship',
            'phone',
        ];
    }

    public function createCreateUserRelativeDTO(): CreateUserRelativeDTO
    {
        return new CreateUserRelativeDTO(
            name: $this->get('name'),
            company_id:$this->get('company_id'),
            global_id:$this->get('global_id'),
            marital_status:$this->get('marital_status'),
            relationship:$this->get('relationship'),
            phone:$this->get('phone'),
        );
    }
}
