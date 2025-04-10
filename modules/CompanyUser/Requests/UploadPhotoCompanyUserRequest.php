<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\UpdatePhotoCompanyUserCommand;

class UploadPhotoCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => 'required|file',
        ];
    }

}
