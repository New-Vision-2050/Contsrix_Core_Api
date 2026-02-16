<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUserCompaniesByEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:company_users,email',
        ];
    }

    public function toDTO(): \Modules\User\DTO\GetUserCompaniesByEmailDTO
    {
        return new \Modules\User\DTO\GetUserCompaniesByEmailDTO(
            email: $this->input('email')
        );
    }
}
