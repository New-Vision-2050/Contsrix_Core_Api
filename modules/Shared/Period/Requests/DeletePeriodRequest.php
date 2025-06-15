<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeletePeriodRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
