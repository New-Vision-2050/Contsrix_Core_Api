<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetBiographyRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
