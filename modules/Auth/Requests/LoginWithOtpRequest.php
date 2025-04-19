<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\LoginWithOtpDTO;
use Ramsey\Uuid\Uuid;
use Modules\Auth\DTO\CreateAuthDTO;

class LoginWithOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'otp' => 'required',
        ];
    }

    public function createLoginDTO(): LoginWithOtpDTO
    {
        return new LoginWithOtpDTO(
            email: $this->get('email'),
            otp: $this->get('otp'),
        );
    }
}
