<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class GetFlashDealWebsiteRequest extends FormRequest
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

