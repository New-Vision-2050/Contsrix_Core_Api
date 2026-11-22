<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Forum\Enums\ForumVisibility;

class SaveForumRequest extends FormRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post') && $this->route('forumId') === null;

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => [$creating ? 'required' : 'sometimes', 'nullable', 'string', 'max:5000'],
            'details' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'logo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'visibility' => [$creating ? 'required' : 'sometimes', 'in:company,scoped'],
            'branch_ids' => ['sometimes', 'array'],
            'branch_ids.*' => ['string'],
            'project_ids' => ['sometimes', 'array'],
            'project_ids.*' => ['string'],
            'admins' => ['sometimes', 'array'],
            'admins.*.user_id' => ['required', 'string'],
            'admins.*.can_manage_groups' => ['sometimes', 'boolean'],
            'admins.*.can_publish_announcements' => ['sometimes', 'boolean'],
            'member_ids' => ['sometimes', 'array'],
            'member_ids.*' => ['string'],
            'notify_members' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,published'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $visibility = $this->input('visibility', ForumVisibility::COMPANY->value);
            $creating = $this->route('forumId') === null;
            if ($creating && $visibility === 'scoped' && $this->input('branch_ids', []) === [] && $this->input('project_ids', []) === []) {
                $validator->errors()->add('branch_ids', __('forum::forum.scope_required'));
            }
            if ($creating && $this->input('status', 'draft') === 'published' && $this->input('admins', []) === []) {
                $validator->errors()->add('admins', __('forum::forum.admin_required'));
            }
        });
    }
}
