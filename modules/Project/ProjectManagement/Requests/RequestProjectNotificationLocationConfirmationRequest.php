<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationLocationConfirmationDTO;

class RequestProjectNotificationLocationConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'distance_meters' => ['nullable', 'numeric', 'min:0'],
            'is_inside_location' => ['required', 'boolean'],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_inside_location')) {
            $value = $this->input('is_inside_location');
            $this->merge([
                'is_inside_location' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function toDTO(): RequestProjectNotificationLocationConfirmationDTO
    {
        return new RequestProjectNotificationLocationConfirmationDTO(
            latitude: $this->filled('latitude') ? (float) $this->input('latitude') : null,
            longitude: $this->filled('longitude') ? (float) $this->input('longitude') : null,
            distanceMeters: $this->filled('distance_meters') ? (float) $this->input('distance_meters') : null,
            isInsideLocation: $this->filled('is_inside_location') ? (bool) $this->input('is_inside_location') : null,
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
        );
    }
}
