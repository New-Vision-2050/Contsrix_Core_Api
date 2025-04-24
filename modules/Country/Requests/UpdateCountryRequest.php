<?php

declare(strict_types=1);

namespace Modules\Country\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Country\Commands\UpdateCountryCommand;
use Modules\Country\Handlers\UpdateCountryHandler;

class UpdateCountryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            "status"=>"required|in:1,0",
            "sms_driver_id" => "required|exists:drivers,id",
        ];
    }

    public function createUpdateCountryCommand(): UpdateCountryCommand
    {
        return new UpdateCountryCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            status: $this->get('status'),
            smsDriverId: Uuid::fromString($this->get('sms_driver_id')),
        );
    }
}
