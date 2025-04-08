<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CompanyUser\Commands\UpdateEmailOtpCommand;

class SendEmailOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email'
        ];
    }

    public function updateEmailOtpCommand(): UpdateEmailOtpCommand
    {
        return new UpdateEmailOtpCommand(
            email: $this->get('email'),
        );
    }
}
