<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportCompaniesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'string|exists:companies,id'
        ];
    }
}