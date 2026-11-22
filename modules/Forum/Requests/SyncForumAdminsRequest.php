<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncForumAdminsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'admins' => ['present', 'array'],
            'admins.*.user_id' => ['required', 'string'],
            'admins.*.can_manage_groups' => ['sometimes', 'boolean'],
            'admins.*.can_publish_announcements' => ['sometimes', 'boolean'],
        ];
    }
}
