<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
        ];
    }

    public function messages(): array
    {
        return [

        ];
    }
}
