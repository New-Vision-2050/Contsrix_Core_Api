<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetActivityLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
