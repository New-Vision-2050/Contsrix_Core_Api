<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forum\Enums\ForumMemberRole;

class SyncForumMembersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'members' => ['present', 'array'],
            'members.*.user_id' => ['required', 'string', 'distinct'],
            'members.*.role' => ['sometimes', Rule::enum(ForumMemberRole::class)],
            'members.*.can_send' => ['sometimes', 'boolean'],
        ];
    }
}
