<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoShopRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
