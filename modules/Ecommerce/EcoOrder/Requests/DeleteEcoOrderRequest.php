<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
