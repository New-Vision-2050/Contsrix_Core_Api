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

    public function getRoleId(): string
    {
        return $this->route('id');
    }

    public function getStatus(): bool
    {
        return $this->get('status');
    }
}
