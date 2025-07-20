<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetStatusRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|boolean',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'status.required' => __('validation.required', ['attribute' => 'status']),
            'status.boolean' => __('validation.boolean', ['attribute' => 'status']),
        ];
    }

    public function getRoleId(): string
    {
        return $this->route('id');
    }

    public function getStatus()
    {
        return $this->get('status');
    }
}
