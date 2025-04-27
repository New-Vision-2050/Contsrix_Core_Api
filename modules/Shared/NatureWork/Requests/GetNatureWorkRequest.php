<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetNatureWorkRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
