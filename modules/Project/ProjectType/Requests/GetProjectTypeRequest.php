<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetProjectTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
