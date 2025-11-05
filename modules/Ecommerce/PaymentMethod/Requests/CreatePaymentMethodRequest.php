<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\PaymentMethod\DTO\CreatePaymentMethodDTO;

class CreatePaymentMethodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreatePaymentMethodDTO(): CreatePaymentMethodDTO
    {
        return new CreatePaymentMethodDTO(
            name: $this->get('name'),
        );
    }
}
