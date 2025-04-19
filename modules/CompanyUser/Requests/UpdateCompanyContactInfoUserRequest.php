<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\UpdateCompanyUserContactInfoCommand;
use Illuminate\Validation\Rule;

class UpdateCompanyContactInfoUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::unique('company_users', 'email')
                    ->ignore(auth()->user()->global_company_user_id, 'global_id')
            ],
            'phone' => [
                'required',
                'phone',
                Rule::unique('company_users', 'phone')
                    ->ignore(auth()->user()->global_company_user_id, 'global_id')
            ],
        ];
    }

    public function createUpdateCompanyUserCommand(): UpdateCompanyUserContactInfoCommand
    {
        return new UpdateCompanyUserContactInfoCommand(
            email: $this->get('email'),
            phone: $this->get("phone")
        );
    }
}
