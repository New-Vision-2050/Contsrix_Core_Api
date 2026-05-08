<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetReportTemplateListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page'  => 'integer|min:1|max:200',
            'page'      => 'integer|min:1',
            'search'    => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
