<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoOrderDetailRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
