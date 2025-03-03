<?php

declare(strict_types=1);

namespace Modules\Setting\Requests\LoginWay;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\DTO\CreateLoginWayDTO;
use Modules\Setting\DTO\CreateSettingDTO;
use Ramsey\Uuid\Uuid;

class UpdateLoginWayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => "required|string",
            "login_options"=>"required|array",
            'login_options.*.login_option' => 'required|string|in:password,otp,barcode',
            'login_options.*.drivers' => 'required_if:login_options.*.login_option,otp|array|nullable',
        ];
    }

    public function createUpdateLoginWayCommand():UpdateLoginWayCommand
    {
        return new UpdateLoginWayCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->input('name'),
            loginOptions: $this->input('login_options')
        );
    }
}
