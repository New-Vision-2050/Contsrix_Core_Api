<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationSafetyViolationDTO;

class RequestProjectNotificationSafetyViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'violations' => ['required', 'array', 'min:1'],
            'violations.*.violation_id' => ['required', 'uuid', 'exists:violations,id'],
            'violations.*.weight' => ['nullable', 'numeric'],
            'violations.*.status' => ['required', Rule::in(['violation_found', 'no_violation', 'not_applicable'])],
            'violations.*.action' => ['nullable', 'string', 'max:50', Rule::in(['stop_work', 'exclude_equipment'])],
            'violations.*.images' => ['nullable', 'array', 'max:3'],
            'violations.*.images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'current_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'current_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function toDTO(): RequestProjectNotificationSafetyViolationDTO
    {
        $violations = $this->input('violations', []);
        $files = $this->file('violations', []) ?: [];

        foreach ($violations as $index => $violation) {
            $images = Arr::wrap($files[$index]['images'] ?? []);
            $violations[$index]['images'] = array_values(array_filter(
                $images,
                fn ($file) => $file instanceof UploadedFile && $file->isValid()
            ));
        }

        return new RequestProjectNotificationSafetyViolationDTO(
            violations: $violations,
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
            currentLatitude: $this->filled('current_latitude') ? (float) $this->input('current_latitude') : null,
            currentLongitude: $this->filled('current_longitude') ? (float) $this->input('current_longitude') : null,
        );
    }
}
