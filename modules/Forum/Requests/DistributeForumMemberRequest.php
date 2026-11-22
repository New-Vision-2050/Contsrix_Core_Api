<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributeForumMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'groups' => ['present', 'array'],
            'groups.*.group_id' => ['required', 'string'],
            'groups.*.role' => ['sometimes', 'in:admin,member'],
        ];
    }
}
