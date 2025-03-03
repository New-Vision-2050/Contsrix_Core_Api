<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\LoginStepAlternativeCommand;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\LoginStepAlternativeDTO;
use Modules\Auth\DTO\LoginStepDTO;
use Modules\Setting\Models\Setting;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LoginStepAlternativeRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'identifier' => 'required',
            'login_option' => 'required',//can be password or otp or anything else
            "token" => "required",
        ];
    }

    public function createLoginStepAlternativeDTO():LoginStepAlternativeDTO
    {
        return new LoginStepAlternativeDTO(
            loginOption: $this->get('login_option'),
            token: $this->get('token'),
            identifier: $this->get('identifier'),
        );
    }
}
