<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetTimeUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
