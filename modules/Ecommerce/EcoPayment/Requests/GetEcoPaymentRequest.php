<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
