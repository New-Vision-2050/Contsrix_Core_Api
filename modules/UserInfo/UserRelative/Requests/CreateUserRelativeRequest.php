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
            'name' => 'nullable|string',
            'user_id'=> 'required|string',
            'marital_status_id'=> 'required|string',
            'relationship'=> 'nullable|string',
            'phone'=> 'nullable|string',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => __('validation.user_relative.name_required'),
            'user_id.required' => __('validation.user_relative.user_id_required'),
            'marital_status_id.required' => __('validation.user_relative.marital_status_id_required'),
            'relationship.required' => __('validation.user_relative.relationship_required'),
            'phone.required' => __('validation.user_relative.phone_required'),
        ];
    }
    public function createCreateUserRelativeDTO(): CreateUserRelativeDTO
    {
        return new CreateUserRelativeDTO(
            name: $this->get('name'),
            company_id:'',
            global_id:'',
            marital_status_id:$this->get('marital_status_id'),
            relationship:$this->get('relationship'),
            phone:$this->get('phone'),
        );
    }
}
