<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteClientRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
