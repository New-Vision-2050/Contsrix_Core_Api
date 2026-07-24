<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;


class UpdateSafetyRecordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'order_type'          => 'nullable|string|max:255',
            'date'                => 'nullable|date',
            'time'                => 'nullable|date_format:H:i',
            'required_score'      => 'nullable|numeric|min:0',
            'earned_score'        => 'nullable|numeric|min:0',
            'percentage'          => 'nullable|numeric|min:0|max:100',
            'consultant_engineer' => 'nullable|string|max:255',
            'consultant'          => 'nullable|string|max:255',
            'contractor_id'       => 'nullable|uuid|exists:project_contractors,id',
            'status'              => 'nullable|in:pending,in_progress,completed',
            'violations'          => 'nullable|array',
            'violations.*.violation_id' => 'required|uuid|exists:violations,id',
            'violations.*.weight'       => 'nullable|numeric',
            'violations.*.status'       => 'nullable|in:violation_found,no_violation,not_applicable',
        ];
    }
}

