<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'string|exists:users,id',
            'format' => 'nullable|string|in:xlsx,csv'
        ];
    }

    public function messages(): array
    {
        return [
            'format.in' => 'The format must be either xlsx or csv'
        ];
    }
}