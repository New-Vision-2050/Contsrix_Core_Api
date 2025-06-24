<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetFeatureRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
