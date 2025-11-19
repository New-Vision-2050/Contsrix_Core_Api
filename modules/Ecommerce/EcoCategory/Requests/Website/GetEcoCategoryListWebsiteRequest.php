<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoCategoryListWebsiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}

