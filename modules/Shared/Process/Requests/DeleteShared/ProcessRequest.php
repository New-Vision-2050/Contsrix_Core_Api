<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteShared/ProcessRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
