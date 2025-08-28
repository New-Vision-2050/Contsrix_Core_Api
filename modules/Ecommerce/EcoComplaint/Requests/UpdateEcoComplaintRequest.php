<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoComplaint\Commands\UpdateEcoComplaintCommand;

class UpdateEcoComplaintRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:65535'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'in_progress', 'resolved', 'closed'])],
        ];
    }
    public function messages(): array
    {
        return [
            'name.string' => __('ecocomplaint::validation.name_string'),
            'name.max' => __('ecocomplaint::validation.name_max'),
            'status.string' => __('ecocomplaint::validation.status_string'),
            'status.in' => __('ecocomplaint::validation.status_in'),
        ];
    }
    public function createUpdateEcoComplaintCommand(): UpdateEcoComplaintCommand
    {
        $validatedData = $this->validated();
        return new UpdateEcoComplaintCommand(
            id: Uuid::fromString($this->route('id')),
            name: $validatedData['name'] ?? null,
            status: $validatedData['status'] ?? null,
        );
    }
}
