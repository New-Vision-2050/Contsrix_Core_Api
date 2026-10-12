<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Leave\LeaveRequest\DTO\CreateLeaveRequestDTO;

class CreateLeaveRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'leave_policy_id' => 'required|uuid|exists:leave_policies,id,company_id,' . tenant('id'),
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string|max:1024',
            'is_emergency' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function createLeaveRequestDTO(): CreateLeaveRequestDTO
    {
        return new CreateLeaveRequestDTO(
            leave_policy_id: (string) $this->get('leave_policy_id'),
            start_date: (string) $this->get('start_date'),
            end_date: (string) $this->get('end_date'),
            reason: (string) $this->get('reason'),
            attachments: $this->get('attachments'),
            is_emergency: (bool) $this->get('is_emergency', false),
            notes: $this->get('notes'),
        );
    }
}
