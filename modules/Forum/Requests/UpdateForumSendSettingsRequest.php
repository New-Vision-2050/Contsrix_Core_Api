<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Forum\Enums\ForumSendPermission;

class UpdateForumSendSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'send_permission' => ['required', Rule::enum(ForumSendPermission::class)],
            'allowed_user_ids' => ['sometimes', 'array'],
            'allowed_user_ids.*' => ['string'],
            'allow_images' => ['required', 'boolean'],
            'allow_voice' => ['required', 'boolean'],
            'allow_files' => ['required', 'boolean'],
        ];
    }
}
