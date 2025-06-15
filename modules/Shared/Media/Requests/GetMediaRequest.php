<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
