<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteTimeUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
