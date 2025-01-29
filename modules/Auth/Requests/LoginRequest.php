<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;

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
            continue_with_otp: (int)Setting::where("key","continue_with_otp")->first()->value ,
        );
    }
}
