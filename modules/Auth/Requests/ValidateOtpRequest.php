<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use App\Rules\PasswordValidation;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\ValidateOtpDTO;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class ValidateOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'otp' => 'required',
            'identifier' => [
                'required',
                function ($attribute, $value, $fail) {
                    $exists = User::query()->withoutTenancy()
                        ->where(function ($query) use ($value) {
                            $query->where('email', $value)
                                  ->orWhere('phone', $value);
                        })
                        ->exists();

                    if ($exists) {
                        $fail(__('validation.user-found'));
                    }
                },
            ],
            // 'type' =>'required',
        ];
    }

    public function createValidateOtpDTO()
    {
        return new ValidateOtpDTO(
            otp: $this->get('otp'),
            identifier: $this->get('identifier'),
        );
    }
}
