<?php

declare(strict_types=1);

namespace Modules\Program\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
