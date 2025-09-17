<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Payment\DTO\CreatePaymentDTO;

class CreatePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreatePaymentDTO(): CreatePaymentDTO
    {
        return new CreatePaymentDTO(
            name: $this->get('name'),
        );
    }
}
