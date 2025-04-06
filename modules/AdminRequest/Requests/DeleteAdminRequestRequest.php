<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteAdminRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
