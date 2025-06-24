<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\DTO\LoginDTO;
use Modules\Setting\Models\Setting;

class getDataForLoginAsAdminRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            "company_id" => "required|exists:companies,id",

        ];
    }

}
