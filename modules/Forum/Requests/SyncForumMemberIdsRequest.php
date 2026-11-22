<?php

declare(strict_types=1);

namespace Modules\Forum\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncForumMemberIdsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'member_ids' => ['present', 'array'],
            'member_ids.*' => ['string'],
        ];
    }
}
