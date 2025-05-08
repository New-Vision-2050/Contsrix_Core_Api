<?php

declare(strict_types=1);

namespace Modules\Country\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetCountryAndStateAndCityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
        ];
    }
}
