<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetModuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
