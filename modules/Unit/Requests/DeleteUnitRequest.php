<?php

declare(strict_types=1);

namespace Modules\Unit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
