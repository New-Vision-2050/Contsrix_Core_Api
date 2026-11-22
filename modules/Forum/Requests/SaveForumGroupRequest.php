<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forum\Enums\ForumMemberRole;
use Modules\Forum\Enums\ForumSendPermission;

class SaveForumGroupRequest extends FormRequest
{
    public function rules(): array
    {
        $required = $this->route('id') === null ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'image', 'max:5120'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'show_name_in_chat' => ['sometimes', 'boolean'],
            'send_permission' => ['sometimes', Rule::enum(ForumSendPermission::class)],
            'allow_images' => ['sometimes', 'boolean'],
            'allow_voice' => ['sometimes', 'boolean'],
            'allow_files' => ['sometimes', 'boolean'],
            'members' => ['sometimes', 'array'],
            'members.*.user_id' => ['required', 'string'],
            'members.*.role' => ['sometimes', Rule::enum(ForumMemberRole::class)],
            'members.*.can_send' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function groupData(): array
    {
        return $this->safe()->only([
            'name',
            'description',
            'category',
            'show_name_in_chat',
            'send_permission',
            'allow_images',
            'allow_voice',
            'allow_files',
        ]);
    }
}
