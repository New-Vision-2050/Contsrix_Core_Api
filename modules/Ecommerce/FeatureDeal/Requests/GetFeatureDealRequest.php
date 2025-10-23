<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetFeatureDealRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
