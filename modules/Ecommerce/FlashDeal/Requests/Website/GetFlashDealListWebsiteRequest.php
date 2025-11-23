<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class GetFlashDealListWebsiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

