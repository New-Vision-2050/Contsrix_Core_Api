<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Leave\PublicHoliday\Commands\UpdatePublicHolidayCommand;
use Modules\Leave\PublicHoliday\Handlers\UpdatePublicHolidayHandler;
use DateTime;

class UpdatePublicHolidayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'country_id' => 'required|string|exists:countries,id',
            'date_start' => 'required|date|date_format:Y-m-d',
            'date_end' => 'required|date|date_format:Y-m-d|after_or_equal:date_start',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('leave.public_holiday.name.required'),
            'name.string' => __('leave.public_holiday.name.string'),
            'name.max' => __('leave.public_holiday.name.max'),
            'country_id.required' => __('leave.public_holiday.country_id.required'),
            'country_id.string' => __('leave.public_holiday.country_id.string'),
            'country_id.exists' => __('leave.public_holiday.country_id.exists'),
            'date_start.required' => __('leave.public_holiday.date_start.required'),
            'date_start.date' => __('leave.public_holiday.date_start.date'),
            'date_start.date_format' => __('leave.public_holiday.date_start.date_format'),
            'date_end.required' => __('leave.public_holiday.date_end.required'),
            'date_end.date' => __('leave.public_holiday.date_end.date'),
            'date_end.date_format' => __('leave.public_holiday.date_end.date_format'),
            'date_end.after_or_equal' => __('leave.public_holiday.date_end.after_or_equal'),
        ];
    }

    public function createUpdatePublicHolidayCommand(): UpdatePublicHolidayCommand
    {
        return new UpdatePublicHolidayCommand(
            id: Uuid::fromString($this->route('id')),
            name: (string) $this->get('name'),
            country_id: (string) $this->get('country_id'),
            date_start: new DateTime($this->get('date_start')),
            date_end: new DateTime($this->get('date_end')),
        );
    }
}
