<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteModulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
