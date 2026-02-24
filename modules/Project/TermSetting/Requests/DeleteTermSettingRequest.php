<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteTermSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
