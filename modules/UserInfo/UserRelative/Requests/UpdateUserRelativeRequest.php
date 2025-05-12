<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserRelative\Commands\UpdateUserRelativeCommand;
use Modules\UserInfo\UserRelative\Handlers\UpdateUserRelativeHandler;

class UpdateUserRelativeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'marital_status'=> 'required|string',
            'relationship'=> 'required|string',
            'phone'=> 'required|string',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => __('validation.user_relative.name_required'),
            'user_id.required' => __('validation.user_relative.user_id_required'),
            'marital_status.required' => __('validation.user_relative.marital_status_required'),
            'relationship.required' => __('validation.user_relative.relationship_required'),
            'phone.required' => __('validation.user_relative.phone_required'),
        ];
    }
    public function createUpdateUserRelativeCommand(): UpdateUserRelativeCommand
    {
        return new UpdateUserRelativeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            marital_status:$this->get('marital_status'),
            relationship:$this->get('relationship'),
            phone:$this->get('phone'),
        );
    }
}
