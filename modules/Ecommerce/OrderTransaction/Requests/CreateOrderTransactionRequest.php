<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\OrderTransaction\DTO\CreateOrderTransactionDTO;

class CreateOrderTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateOrderTransactionDTO(): CreateOrderTransactionDTO
    {
        return new CreateOrderTransactionDTO(
            name: $this->get('name'),
        );
    }
}
