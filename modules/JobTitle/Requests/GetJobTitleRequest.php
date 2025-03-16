<?php

declare(strict_types=1);

namespace Modules\JobTitle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetJobTitleRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
