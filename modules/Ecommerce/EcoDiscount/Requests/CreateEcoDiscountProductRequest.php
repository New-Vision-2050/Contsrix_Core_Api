<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoDiscount\Commands\UpdateEcoDiscountProductCommand;
use Ramsey\Uuid\Uuid;

class CreateEcoDiscountProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'has_discount' => 'sometimes|boolean',
            'discount_amount' => 'sometimes|numeric|min:0',
            'discount_percentage' => 'sometimes|numeric|min:0|max:100',
            'discount_start_date' => 'sometimes|date',
            'discount_end_date' => 'sometimes|date|after:discount_start_date',
            'max_discount_amount' => 'sometimes|numeric|min:0',
        ];
    }

    public function createUpdateEcoDiscountProductCommand(): UpdateEcoDiscountProductCommand
    {
        return new UpdateEcoDiscountProductCommand(
            id: Uuid::fromString((string) $this->route('id')),
            hasDiscount: $this->has('has_discount') ? (bool) $this->get('has_discount') : null,
            discountAmount: $this->has('discount_amount') ? (float) $this->get('discount_amount') : null,
            discountPercentage: $this->has('discount_percentage') ? (float) $this->get('discount_percentage') : null,
            discountStartDate: $this->has('discount_start_date') ? (string) $this->get('discount_start_date') : null,
            discountEndDate: $this->has('discount_end_date') ? (string) $this->get('discount_end_date') : null,
            maxDiscountAmount: $this->has('max_discount_amount') ? (float) $this->get('max_discount_amount') : null,
        );  
    }
}
    