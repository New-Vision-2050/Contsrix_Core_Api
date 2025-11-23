<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class GetSettingPageByTypeWebsiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

