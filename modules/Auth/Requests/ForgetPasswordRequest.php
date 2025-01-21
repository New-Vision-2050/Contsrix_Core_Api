<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;
use Modules\Auth\DTO\CreateAuthDTO;

class ForgetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function createLoginDTO(): LoginDTO
    {
        return new LoginDTO(
            email: $this->get('email'),
        );
    }
}
