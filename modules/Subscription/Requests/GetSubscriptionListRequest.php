<?php

declare(strict_types=1);

namespace Modules\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetSubscriptionListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}
