<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Broker;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetBrokerRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
