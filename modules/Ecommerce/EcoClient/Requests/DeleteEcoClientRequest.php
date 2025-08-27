<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
