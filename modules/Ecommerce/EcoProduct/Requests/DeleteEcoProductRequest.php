<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
