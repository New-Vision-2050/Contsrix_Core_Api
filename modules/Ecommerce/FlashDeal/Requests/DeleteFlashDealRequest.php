<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteFlashDealRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
