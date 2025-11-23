<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class GetFeatureWebsiteRequest extends FormRequest
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

