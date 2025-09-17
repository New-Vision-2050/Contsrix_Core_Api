<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
