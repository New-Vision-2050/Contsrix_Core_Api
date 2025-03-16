<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Currency\DTO\CreateCurrencyDTO;

class CreateCurrencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateCurrencyDTO(): CreateCurrencyDTO
    {
        return new CreateCurrencyDTO(
            name: $this->get('name'),
        );
    }
}
