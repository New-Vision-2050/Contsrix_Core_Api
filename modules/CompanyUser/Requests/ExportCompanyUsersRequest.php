<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\Handlers\UpdateCompanyUserHandler;

class ExportCompanyUsersRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            "company_user_ids" => "required|array",
            "company_user_ids.*" => "required|exists:company_users,id",
        ];
    }


}
