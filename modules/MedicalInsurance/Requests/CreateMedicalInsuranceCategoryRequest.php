<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\MedicalInsurance\DTO\CreateMedicalInsuranceCategoryDTO;

class CreateMedicalInsuranceCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['medical_insurance_id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'medical_insurance_id' => 'required|uuid',
            'name'                 => 'required|string|max:255',
            'type'                 => 'nullable|string|max:255',
            'coverage_limit'       => 'required|numeric|min:0',
            'description'          => 'required|string',
        ];
    }

    public function createDTO(): CreateMedicalInsuranceCategoryDTO
    {
        return new CreateMedicalInsuranceCategoryDTO(
            medicalInsuranceId: $this->validated('medical_insurance_id'),
            name: $this->get('name'),
            coverageLimit: (float) $this->get('coverage_limit'),
            description: $this->get('description'),
            type: $this->get('type'),
        );
    }
}
