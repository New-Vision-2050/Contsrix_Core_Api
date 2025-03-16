<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\ChangeEmailCommand;
use Modules\Auth\DTO\LoginDTO;
use Modules\Setting\Models\Setting;

class ChangeEmailRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            "token" => "required",
            'email' => 'nullable',
            'new_email' => 'required|email|unique:users,email',
            'new_email_confirmation' => 'required|email|same:new_email',
        ];
    }

    public function createChangeEmailCommand(): ChangeEmailCommand
    {
        return new ChangeEmailCommand(
            token: $this->get('token'),
            email: $this->get('email'),
            newEmail: $this->get('new_email'),
        );
    }
}
