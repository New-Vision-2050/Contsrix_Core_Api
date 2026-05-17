<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceCategoryDTO;

class CreateMedicalInsuranceCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'type'           => 'nullable|string|max:255',
            'coverage_limit' => 'required|numeric|min:0',
            'description'    => 'required|string',
        ];
    }

    public function createDTO(): CreateMedicalInsuranceCategoryDTO
    {
        return new CreateMedicalInsuranceCategoryDTO(
            medicalInsuranceId: $this->route('id'),
            name: $this->get('name'),
            coverageLimit: (float) $this->get('coverage_limit'),
            description: $this->get('description'),
            type: $this->get('type'),
        );
    }
}
