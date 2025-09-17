<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class SendEmailToUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'uuid', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required.',
            'user_id.string' => 'User ID must be a string.',
            'user_id.uuid' => 'User ID must be a valid UUID.',
            'user_id.exists' => 'The specified user does not exist.',
        ];
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->input('user_id'));
    }

    public function authorize(): bool
    {
        return true;
    }
}
