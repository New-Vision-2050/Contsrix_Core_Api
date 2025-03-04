<?php

declare(strict_types=1);

namespace Modules\Audit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetAuditRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
