<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetTermServiceSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
