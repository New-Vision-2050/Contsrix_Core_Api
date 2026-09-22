<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\Leave\PublicHoliday\DTO\CreatePublicHolidayDTO;
use Modules\Leave\PublicHoliday\Rules\MonthDay;
use Modules\Leave\PublicHoliday\Services\AnnualHolidayDateRange;

class CreatePublicHolidayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'branch_id' => ['required', 'integer', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')->where('company_id', tenant('id'))],
            'date_start' => ['required', new MonthDay()],
            'date_end' => ['required', new MonthDay()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('leave.public_holiday.name.required'),
            'name.string' => __('leave.public_holiday.name.string'),
            'name.max' => __('leave.public_holiday.name.max'),
            'branch_id.required' => __('leave.public_holiday.branch_id.required'),
            'branch_id.integer' => __('leave.public_holiday.branch_id.integer'),
            'branch_id.exists' => __('leave.public_holiday.branch_id.exists'),
            'date_start.required' => __('leave.public_holiday.date_start.required'),
            'date_end.required' => __('leave.public_holiday.date_end.required'),
        ];
    }

    public function createCreatePublicHolidayDTO(): CreatePublicHolidayDTO
    {
        $data = $this->validated();
        [$start, $end] = (new AnnualHolidayDateRange())->nextValidYear(
            $data['date_start'], $data['date_end'], (int) now()->year,
        );

        return new CreatePublicHolidayDTO(
            name: (string) $data['name'],
            branch_id: (int) $data['branch_id'],
            date_start: $start,
            date_end: $end,
        );
    }
}
