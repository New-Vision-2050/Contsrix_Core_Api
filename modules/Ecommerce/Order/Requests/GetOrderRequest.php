<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
