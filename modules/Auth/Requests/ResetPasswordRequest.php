<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use App\Rules\PasswordValidation;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'otp' => 'required',
            'identifier' => 'required',
            "password"=> [
                new PasswordValidation(),
            'confirmed']
        ];
    }

    public function createResetPasswordCommand()
    {
        return new ResetPasswordCommand(
            otp: $this->get('otp'),
            password: $this->get('password'),
            identifier: $this->get('identifier'),
        );
    }
}
