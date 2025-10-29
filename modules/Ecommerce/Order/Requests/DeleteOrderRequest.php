<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
