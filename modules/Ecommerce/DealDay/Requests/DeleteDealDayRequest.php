<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteDealDayRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
