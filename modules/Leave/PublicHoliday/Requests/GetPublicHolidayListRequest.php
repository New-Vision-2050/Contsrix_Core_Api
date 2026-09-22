<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetPublicHolidayListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => ['sometimes', 'integer', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')->where('company_id', tenant('id'))],
            'search' => 'sometimes|string|max:255',
            'date_start' => ['sometimes', new \Modules\Leave\PublicHoliday\Rules\MonthDay()],
            'date_end' => ['sometimes', new \Modules\Leave\PublicHoliday\Rules\MonthDay()],
            'year' => 'required_with:month|integer|between:1900,9999',
            'month' => 'sometimes|integer|between:1,12',
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ];
    }
}
