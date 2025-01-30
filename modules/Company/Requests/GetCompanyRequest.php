<?php

declare(strict_types=1);

namespace Modules\Company\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
