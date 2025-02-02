<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use App\Rules\PasswordValidation;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Commands\ResendOtpCommand;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;

class ResendOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required',

        ];
    }

    public function createResendOtpCommand()
    {
        return new ResendOtpCommand(
            email: $this->get('email'),
        );
    }
}
