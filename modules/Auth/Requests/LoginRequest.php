<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;
use Modules\Auth\DTO\CreateAuthDTO;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function createLoginDTO(): LoginDTO
    {
        return new LoginDTO(
            email: $this->get('email'),
            password: $this->get('password'),
            continue_with_otp: $this->get('continue_with_otp')!=null ? (int)$this->get('continue_with_otp'):0,
        );
    }
}
