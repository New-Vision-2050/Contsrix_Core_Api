<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
