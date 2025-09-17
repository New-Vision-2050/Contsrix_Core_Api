<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoCurrencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
