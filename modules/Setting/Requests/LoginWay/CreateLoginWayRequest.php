<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\LoginWay;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Ramsey\Uuid\Uuid;

class CreateLoginWayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => "required|string",
            "company_id" => "required|exists:companies,id",
            "login_options"=>"required|array",
            'login_options.*.login_option' => 'required|string|in:password,otp,barcode',
            'login_options.*.driver_ids' => 'required_if:login_options.*.login_option,otp|array|nullable',
        ];
    }

    public function createCreateLoginWayDTO(): CreateLoginWayDTO
    {
        return new CreateLoginWayDTO(
            name: $this->input('name'),
            loginOptions: $this->input('login_options'),
            companyId: Uuid::fromString($this->input('company_id')),
        );
    }
}
