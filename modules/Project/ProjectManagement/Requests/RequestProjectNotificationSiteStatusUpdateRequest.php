<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationSiteStatusUpdateDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotificationUpdateSiteStatus;

class RequestProjectNotificationSiteStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:5000'],
            'status_id' => ['nullable', 'string', $this->resolveUpdateSiteStatusRule()],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
            'current_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'current_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function toDTO(): RequestProjectNotificationSiteStatusUpdateDTO
    {
        return new RequestProjectNotificationSiteStatusUpdateDTO(
            description: $this->input('description'),
            statusId: $this->input('status_id'),
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
            files: $this->hasFile('files') ? $this->file('files') : null,
            currentLatitude: $this->filled('current_latitude') ? (float) $this->input('current_latitude') : null,
            currentLongitude: $this->filled('current_longitude') ? (float) $this->input('current_longitude') : null,
        );
    }

    private function resolveUpdateSiteStatusRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $exists = ProjectNotificationUpdateSiteStatus::query()
                ->where(function ($query) use ($value) {
                    $query->where('id', $value)
                        ->orWhere('key', $value);
                })
                ->where('is_active', true)
                ->exists();

            if (! $exists) {
                $fail('The selected :attribute is invalid.');
            }
        };
    }
}
