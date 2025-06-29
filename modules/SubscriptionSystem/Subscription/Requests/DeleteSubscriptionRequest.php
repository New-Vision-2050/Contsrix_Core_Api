<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
