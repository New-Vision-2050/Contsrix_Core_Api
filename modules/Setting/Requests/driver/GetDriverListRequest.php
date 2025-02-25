<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\driver;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetDriverListRequest extends FormRequest
{
    public function rules(): array
    {
        return [

        ];
    }
}
