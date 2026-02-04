<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\Contactinfo\Commands\UpdateAddressCommand;
use Modules\UserInfo\Contactinfo\Handlers\UpdateContactinfoHandler;

class UpdateAddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'address' => 'nullable',
            'postal_code' => 'nullable|string',
        ];
    }

    public function createUpdateAddressCommand(): UpdateAddressCommand
    {
        return new UpdateAddressCommand(
            company_id: '',
            global_id: '',
            address: $this->get('address'),
            postal_code: $this->get('postal_code'),

        );
    }
}
