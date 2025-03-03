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
            "login_options"=>"required|array",
            'login_options.*.login_option' => 'required|string|in:password,otp,barcode',
            'login_options.*.drivers' => 'required_if:login_options.*.login_option,otp|array|in:sms,mail,social|nullable',
            'login_options.*.login_option_alternatives' => 'array|in:sms,mail,social,password|nullable',
        ];
    }

    public function createCreateLoginWayDTO(): CreateLoginWayDTO
    {
        return new CreateLoginWayDTO(
            name: $this->input('name'),
            loginOptions: $this->input('login_options')
        );
    }
}
