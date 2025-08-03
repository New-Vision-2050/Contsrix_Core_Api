<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetStatusPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required',
        ];
    }

    public function getPermissionId(): string
    {
        return $this->route('id');
    }

    public function getStatus()
    {
        return $this->get('status');
    }
}
