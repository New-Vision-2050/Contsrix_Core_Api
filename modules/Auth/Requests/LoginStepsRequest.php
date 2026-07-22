<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\LoginStepDTO;
use Modules\Setting\Models\Setting;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LoginStepsRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'identifier' => 'required',
            'password' => 'required',//can be password or otp or anything else
            "token" => "required",
            'imei' => 'nullable|string',
        ];
    }

    public function createLoginStepDTO(): LoginStepDTO
    {
        return new LoginStepDTO(
            identifier: $this->get('identifier'),
            password: $this->get('password'),
            token: $this->get('token'),
            imei: $this->get('imei'),
        );
    }
}
