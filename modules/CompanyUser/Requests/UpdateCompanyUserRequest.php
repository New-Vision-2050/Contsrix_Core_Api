<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;

class UpdateCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'phone' => 'required|unique:company_users,phone,'.$this->route("id"),
            'phone_code' => 'required',
            'email' => 'required|email|unique:company_users,email,'.$this->route("id"),
            'border_number' => 'present|nullable|unique:company_users,border_number,'.$this->route("id"),
            'residence' => 'present|nullable|unique:company_users,residence,'.$this->route("id"),
            'passport' => 'present|nullable|unique:company_users,passport,'.$this->route("id"),
            'identity' => 'present|nullable|unique:company_users,identity,'.$this->route("id"),
        ];
    }

    public function createUpdateCompanyUserCommand(): UpdateCompanyUserCommand
    {
        return new UpdateCompanyUserCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            email: $this->get('email'),
            country_id:(string) $this->get('country_id'),
            phone: (string)$this->get('phone'),
            phone_code:(string) $this->get('phone_code'),
            border_number:(string) $this->get('border_number'),
            residence: (string)$this->get('residence'),
            identity:(string) $this->get('identity'),
            passport: $this->get('passport'),
        );
    }
}
