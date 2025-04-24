<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\Commands\UpdateTimeZoneCompanyUserCommand;

class UpdateTimeZoneCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id',
            'time_zone_id' => 'required|exists:time_zones,id',
            'language_id' => 'required|exists:languages,id',
            'currency_id' => 'required|exists:currencies,id',
        ];
    }

    public function updateTimeZoneUpdateCompanyUserCommand(): UpdateTimeZoneCompanyUserCommand
    {
        return new UpdateTimeZoneCompanyUserCommand(
            id: Uuid::fromString($this->route('id')),
            country_id: $this->get('country_id'),
            time_zone_id:$this->get('time_zone_id'),
            language_id: $this->get('language_id'),
            currency_id: $this->get('currency_id'),
        );
    }
}
