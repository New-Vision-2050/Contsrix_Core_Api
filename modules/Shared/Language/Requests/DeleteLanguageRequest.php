<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteLanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
