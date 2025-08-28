<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
