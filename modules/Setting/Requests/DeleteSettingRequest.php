<?php

declare(strict_types=1);

namespace Modules\Setting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required'
        ];
    }
}
