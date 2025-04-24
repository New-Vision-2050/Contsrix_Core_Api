<?php

declare(strict_types=1);

namespace Modules\Country\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteCountryRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
