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
            'features.*.feature_id' => 'required|uuid|exists:features,id',
            'features.*.module_id' => 'required|uuid|exists:modules,id',
        ];
    }

    public function createCreateProgramSystemDTO(): CreateProgramSystemDTO
    {
        return new CreateProgramSystemDTO(
            name: $this->get('name'),
            features: $this->get('features', []),
        );
    }
}
