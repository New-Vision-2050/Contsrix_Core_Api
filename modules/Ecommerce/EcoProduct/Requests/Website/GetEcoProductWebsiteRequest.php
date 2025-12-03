<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class GetEcoProductWebsiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }
}

