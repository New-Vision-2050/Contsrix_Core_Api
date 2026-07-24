<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSafetyRecordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'morphable_type'      => 'required|string|in:project_notification,project_order_permit',
            'morphable_id'        => 'required|uuid',
            'assigned_user_ids'   => 'required|array|min:1',
            'assigned_user_ids.*' => 'required|uuid|exists:users,id',
            'order_type'          => 'nullable|string|max:255',
            'date'                => 'nullable|date',
            'time'                => 'nullable|date_format:H:i',
            'required_score'      => 'nullable|numeric|min:0',
            'earned_score'        => 'nullable|numeric|min:0',
            'percentage'          => 'nullable|numeric|min:0|max:100',
            'consultant_engineer' => 'nullable|string|max:255',
            'consultant'          => 'nullable|string|max:255',
            'contractor_id'       => 'nullable|uuid|exists:project_contractors,id',
            'violations'          => 'nullable|array',
            'violations.*.violation_id' => 'required|uuid|exists:violations,id',
            'violations.*.weight'       => 'nullable|numeric',
            'violations.*.status'       => 'nullable|in:violation_found,no_violation,not_applicable',
        ];
    }
}
