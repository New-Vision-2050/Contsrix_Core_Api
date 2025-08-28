<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoOrder\DTO\CreateEcoOrderDTO;

class CreateEcoOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateEcoOrderDTO(): CreateEcoOrderDTO
    {
        return new CreateEcoOrderDTO(
            name: $this->get('name'),
        );
    }
}
