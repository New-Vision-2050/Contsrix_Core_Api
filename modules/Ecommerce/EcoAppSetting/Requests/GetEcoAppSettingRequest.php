<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEcoAppSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
