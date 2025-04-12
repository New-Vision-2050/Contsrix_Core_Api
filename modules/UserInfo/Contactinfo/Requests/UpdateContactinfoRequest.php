<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\Contactinfo\Commands\UpdateContactinfoCommand;
use Modules\UserInfo\Contactinfo\Handlers\UpdateContactinfoHandler;

class UpdateContactinfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'other_phone' => 'required|string',
            'phone' => 'required|string',
            'phone_code' => 'required|string',
            'landline_number' => 'required|string',
        ];
    }

    public function createUpdateContactinfoCommand(): UpdateContactinfoCommand
    {
        return new UpdateContactinfoCommand(
            id: Uuid::fromString($this->route('id')),
            email: $this->get('email'),
            other_phone: $this->get('other_phone'),
            phone: $this->get('phone'),
            phone_code: $this->get('phone_code'),
            landline_number: $this->get('landline_number'),
        );
    }
}
