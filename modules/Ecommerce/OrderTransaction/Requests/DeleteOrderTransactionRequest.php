<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteOrderTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
