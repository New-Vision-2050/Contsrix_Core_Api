<?php

declare(strict_types=1);

namespace Modules\Shared\University\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUniversityListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}
