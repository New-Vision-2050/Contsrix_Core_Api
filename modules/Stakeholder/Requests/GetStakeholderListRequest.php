<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetStakeholderListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
