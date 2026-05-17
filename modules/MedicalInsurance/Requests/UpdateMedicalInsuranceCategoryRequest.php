<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\MedicalInsurance\Commands\UpdateMedicalInsuranceCategoryCommand;

class UpdateMedicalInsuranceCategoryRequest extends FormRequest
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

    public function createCommand(): UpdateMedicalInsuranceCategoryCommand
    {
        return new UpdateMedicalInsuranceCategoryCommand(
            id: Uuid::fromString($this->route('category_id')),
            name: $this->get('name'),
            coverageLimit: (float) $this->get('coverage_limit'),
            description: $this->get('description'),
            type: $this->get('type'),
        );
    }
}
