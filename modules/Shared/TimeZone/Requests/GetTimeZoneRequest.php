<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetTimeZoneRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
