<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetPaymentMethodRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
