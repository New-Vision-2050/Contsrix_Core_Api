<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\Feature\DTO\CreateFeatureDTO;

class CreateFeatureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateFeatureDTO(): CreateFeatureDTO
    {
        return new CreateFeatureDTO(
            name: $this->get('name'),
        );
    }
}
