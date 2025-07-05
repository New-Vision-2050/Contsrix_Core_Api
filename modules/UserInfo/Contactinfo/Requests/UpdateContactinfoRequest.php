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
            'other_phone' => 'nullable|phone',
            'code_other_phone' => 'nullable',
            'phone' => 'required|string|phone',
            'phone_code' => 'required|string',
            'landline_number' => 'nullable',
        ];
    }

    public function createUpdateContactinfoCommand(): UpdateContactinfoCommand
    {
        return new UpdateContactinfoCommand(
            company_id: '',
            global_id: '',
            email: $this->get('email'),
            other_phone: $this->get('other_phone'),
            code_other_phone: $this->get('code_other_phone'),
            phone: $this->get('phone'),
            phone_code: $this->get('phone_code'),
            landline_number: $this->get('landline_number'),
        );
    }
}
