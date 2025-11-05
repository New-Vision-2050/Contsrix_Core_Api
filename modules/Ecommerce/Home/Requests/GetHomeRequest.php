<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetHomeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
