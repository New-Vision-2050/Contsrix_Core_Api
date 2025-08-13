<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportPackageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['sometimes', Rule::in(['xlsx', 'csv'])],
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['uuid', 'exists:packages,id'],
        ];
    }

    /**
     * Get filters from the request
     *
     * @return array
     */
    public function getFilters(): array
    {
        return array_filter([
            'ids' => $this->get('ids'),
        ]);
    }
}
