<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetPackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
