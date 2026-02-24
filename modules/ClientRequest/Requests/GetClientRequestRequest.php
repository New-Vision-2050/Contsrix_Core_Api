<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetClientRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
