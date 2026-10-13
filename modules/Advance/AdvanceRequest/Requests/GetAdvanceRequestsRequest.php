<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Advance\AdvanceRequest\Models\AdvanceRequest;

class GetAdvanceRequestsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:' . implode(',', [
                AdvanceRequest::STATUS_PENDING,
                AdvanceRequest::STATUS_APPROVED,
                AdvanceRequest::STATUS_REJECTED,
                AdvanceRequest::STATUS_CANCELLED,
            ]),
            'advance_policy_id' => 'nullable|uuid',
            'user_id' => 'nullable|uuid',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
