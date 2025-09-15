<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoShopAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
