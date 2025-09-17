<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoPayment\DTO\CreateEcoPaymentDTO;

class CreateEcoPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateEcoPaymentDTO(): CreateEcoPaymentDTO
    {
        return new CreateEcoPaymentDTO(
            name: $this->get('name'),
        );
    }
}
