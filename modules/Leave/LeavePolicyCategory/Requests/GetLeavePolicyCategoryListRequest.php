<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLeavePolicyCategoryListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}
