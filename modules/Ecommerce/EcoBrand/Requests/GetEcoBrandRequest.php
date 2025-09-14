<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoBrandRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
