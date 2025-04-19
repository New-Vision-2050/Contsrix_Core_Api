<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteQualificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
