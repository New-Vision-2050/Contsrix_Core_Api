<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;
use Modules\Auth\DTO\CreateAuthDTO;

class LogoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "email"=>"required|email",

        ];
    }

    public function LogoutCommand()
    {
        return new  ForgetPasswordCommand(email: $this->get("email"));
    }


}
