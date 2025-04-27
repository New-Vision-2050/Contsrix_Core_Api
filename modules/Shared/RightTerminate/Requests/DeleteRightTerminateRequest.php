<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteRightTerminateRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
