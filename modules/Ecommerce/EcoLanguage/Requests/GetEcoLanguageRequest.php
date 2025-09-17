<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
