<?php

declare(strict_types=1);

namespace Modules\Process\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetProcessRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
