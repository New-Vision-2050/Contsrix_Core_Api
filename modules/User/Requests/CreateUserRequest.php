<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use App\Rules\PasswordValidation;
use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\User\DTO\CreateUserDTO;

class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'name' => 'required',
//            'password' => ['required', new PasswordValidation()],
            "phone" => "required|unique:users,phone",
            "phone_code" => "required|exists:countries,phonecode",
        ];
    }

    public function createCreateUserDTO(): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $this->get('name'),
            email: $this->get('email'),
//            password: $this->get('password'),
            phone: $this->get('phone'),
            phoneCode: $this->get('phone_code'),
        );
    }
}
