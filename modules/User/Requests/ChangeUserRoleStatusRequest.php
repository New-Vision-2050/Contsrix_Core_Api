<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Illuminate\Validation\Rule;

class ChangeUserRoleStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|string|uuid|exists:users,id',
            'role_id' => ['required',Rule::in([CompanyUserRole::EMPLOYEE , CompanyUserRole::CLIENT , CompanyUserRole::BROKER])],
            'status' => [
                'required',
                'integer',
                Rule::in([
                    CompanyUserStatus::ACTIVE->value,
                    CompanyUserStatus::INACTIVE->value,
                    CompanyUserStatus::PENDING->value,
                ])
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'The user ID is required.',
            'user_id.uuid' => 'The user ID must be a valid UUID.',
            'user_id.exists' => 'The specified user does not exist.',
            'role_id.required' => 'The role ID is required.',

            'status.required' => 'The status is required.',
            'status.integer' => 'The status must be an integer.',
            'status.in' => 'The status must be one of: ' . implode(', ', [
                CompanyUserStatus::ACTIVE->value . ' (Active)',
                CompanyUserStatus::INACTIVE->value . ' (Inactive)',
                CompanyUserStatus::PENDING->value . ' (Pending)'
            ]),
        ];
    }

    /**
     * Get the user ID from the request
     */
    public function getUserId(): string
    {
        return $this->input('user_id');
    }

    /**
     * Get the role ID from the request
     */
    public function getRoleId():mixed
    {
        return $this->input('role_id');
    }

    /**
     * Get the status from the request
     */
    public function getStatus(): int
    {
        return (int) $this->input('status');
    }
}
