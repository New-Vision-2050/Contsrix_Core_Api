<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoComplaint\DTO\CreateEcoComplaintDTO;
use Illuminate\Validation\Rule;

class CreateEcoComplaintRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'eco_client_id' => ['required', 'uuid', 'exists:eco_clients,id'],
            'name' => ['required', 'string', 'max:65535'], // text column, so higher max
            'status' => ['nullable', 'string', Rule::in(['pending', 'in_progress', 'resolved', 'closed'])],
        ];
    }
    public function messages(): array
    {
        return [
            'eco_client_id.required' => __('ecocomplaint::validation.eco_client_id_required'),
            'eco_client_id.uuid' => __('ecocomplaint::validation.eco_client_id_uuid'),
            'eco_client_id.exists' => __('ecocomplaint::validation.eco_client_id_exists'),
            'name.required' => __('ecocomplaint::validation.name_required'),
            'name.string' => __('ecocomplaint::validation.name_string'),
            'name.max' => __('ecocomplaint::validation.name_max'),
            'status.string' => __('ecocomplaint::validation.status_string'),
            'status.in' => __('ecocomplaint::validation.status_in'),
        ];
    }
    public function createCreateEcoComplaintDTO(): CreateEcoComplaintDTO
    {
        $validatedData = $this->validated();

        return new CreateEcoComplaintDTO(
            companyId: Uuid::fromString(tenant("id")),
            ecoClientId: $validatedData['eco_client_id'],
            name: $validatedData['name'],
            status: $validatedData['status'] ?? 'pending',
        );
    }
}
