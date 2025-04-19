<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetCurrencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
