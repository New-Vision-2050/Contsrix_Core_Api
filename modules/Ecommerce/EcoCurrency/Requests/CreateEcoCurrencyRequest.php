<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoCurrency\DTO\CreateEcoCurrencyDTO;

class CreateEcoCurrencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateEcoCurrencyDTO(): CreateEcoCurrencyDTO
    {
        return new CreateEcoCurrencyDTO(
            name: $this->get('name'),
        );
    }
}
