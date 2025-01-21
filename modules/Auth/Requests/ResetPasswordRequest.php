<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;
use Modules\Auth\DTO\CreateAuthDTO;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'otp' => 'required',
            "password"=> ['required',
            'min:8',
            'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
            'confirmed']
        ];
    }

    public function createResetPasswordCommand()
    {
        return new ResetPasswordCommand(
            otp: $this->get('otp'),
            password: $this->get('password'),
        );
    }
}
