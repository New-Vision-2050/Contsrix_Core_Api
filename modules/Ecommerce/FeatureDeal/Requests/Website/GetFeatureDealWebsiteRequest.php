<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class GetFeatureDealWebsiteRequest extends FormRequest
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

