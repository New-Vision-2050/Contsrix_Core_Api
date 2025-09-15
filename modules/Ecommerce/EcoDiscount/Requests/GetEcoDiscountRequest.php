<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoDiscountRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
