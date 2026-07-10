<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReassignProjectNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_user_ids'  => ['required', 'array', 'min:1'],
            'assigned_user_ids.*' => ['uuid', 'exists:users,id'],
        ];
    }
}
