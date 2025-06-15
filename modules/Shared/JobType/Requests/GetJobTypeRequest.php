<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetJobTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
