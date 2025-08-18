<?php

declare(strict_types=1);

namespace Modules\Test\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
