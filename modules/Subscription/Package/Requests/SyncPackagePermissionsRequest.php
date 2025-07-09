<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPackagePermissionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
