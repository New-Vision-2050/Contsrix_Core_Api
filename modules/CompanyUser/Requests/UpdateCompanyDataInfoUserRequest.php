<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\UpdateCompanyUserDataInfoCommand;

class UpdateCompanyDataInfoUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            "nickname"=> 'nullable',
            "gender"=> 'present|nullable',
            "is_default"=> 'present|nullable|in:0,1',
            "birthdate_gregorian"=> 'nullable',
            "birthdate_hijri"=> 'nullable',
            "nationality"=> 'required',
        ];
    }

    public function createUpdateCompanyUserCommand(): UpdateCompanyUserDataInfoCommand
    {
        return new UpdateCompanyUserDataInfoCommand(
            name: $this->get('name'),
            nickname: $this->get("nickname")??'',
            gender: $this->get("gender"),
            birthdate_gregorian: $this->get("birthdate_gregorian") ?? '', // Ensure it's an empty string if null
            birthdate_hijri: $this->get("birthdate_hijri"),
            is_default: $this->get("is_default"),
            nationality: $this->get("nationality"),
        );
    }
}
