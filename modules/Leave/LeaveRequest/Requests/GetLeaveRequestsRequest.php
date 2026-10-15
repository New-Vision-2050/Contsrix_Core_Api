<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Attendance\Models\LeaveRequest;

class GetLeaveRequestsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:' . implode(',', [
                LeaveRequest::STATUS_PENDING,
                LeaveRequest::STATUS_APPROVED,
                LeaveRequest::STATUS_REJECTED,
                LeaveRequest::STATUS_CANCELLED,
            ]),
            'leave_policy_id' => 'nullable|uuid',
            'user_id' => 'nullable|uuid',
            'year' => 'nullable|integer|min:2000|max:2100',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
