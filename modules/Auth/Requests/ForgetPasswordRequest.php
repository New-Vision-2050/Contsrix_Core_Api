<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Ramsey\Uuid\Uuid;

class ForgetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'identifier' => 'required|email',
        ];
    }

    public function createForgetPasswordCommand()
    {
        return new ForgetPasswordCommand(identifier:$this->get('identifier'));
    }


}
