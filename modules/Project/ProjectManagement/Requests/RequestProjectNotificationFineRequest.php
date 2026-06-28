<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationFineDTO;

class RequestProjectNotificationFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name_ar' => ['required', 'string', 'max:255'],
            'items.*.name_en' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'items.*.total_amount' => ['required', 'numeric', 'min:0'],
            'items.*.sort_order' => ['nullable', 'integer'],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function toDTO(): RequestProjectNotificationFineDTO
    {
        return new RequestProjectNotificationFineDTO(
            reason: $this->input('reason'),
            items: $this->input('items', []),
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
            files: $this->hasFile('files') ? $this->file('files') : null,
        );
    }
}
