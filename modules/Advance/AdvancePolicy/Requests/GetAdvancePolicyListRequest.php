<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAdvancePolicyListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'name' => 'nullable|string|max:255',
        ];
    }
}
