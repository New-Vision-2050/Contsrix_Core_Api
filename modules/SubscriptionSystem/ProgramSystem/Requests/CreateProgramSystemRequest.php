<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\ProgramSystem\DTO\CreateProgramSystemDTO;

class CreateProgramSystemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'features' => 'required|array',
            'features.*' => 'required|uuid|exists:features,id',
            'company_fields' => 'nullable|array',
            'company_fields.*' => 'uuid|exists:company_fields,id',
            'business_types' => 'nullable|array',
            'business_types.*' => 'uuid|exists:business_types,id',
        ];
    }

    public function createCreateProgramSystemDTO(): CreateProgramSystemDTO
    {
        return new CreateProgramSystemDTO(
            name: $this->get('name'),
            features: $this->get('features', []),
            companyFields: $this->get('company_fields', []),
            businessTypes: $this->get('business_types', []),
        );
    }
}
