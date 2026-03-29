<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetProcedureSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
