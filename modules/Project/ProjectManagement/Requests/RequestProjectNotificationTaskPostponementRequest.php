<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationTaskPostponementDTO;

class RequestProjectNotificationTaskPostponementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_task_date' => ['required', 'date_format:Y-m-d'],
            'new_task_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
            'internal_procedure_setting_id' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): RequestProjectNotificationTaskPostponementDTO
    {
        return new RequestProjectNotificationTaskPostponementDTO(
            newTaskDate: $this->input('new_task_date'),
            newTaskTime: $this->input('new_task_time'),
            reason: $this->input('reason'),
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
        );
    }
}
