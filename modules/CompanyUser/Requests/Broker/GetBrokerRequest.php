<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Broker;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetBrokerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'                                => 'integer|min:1',
            'per_page'                            => 'integer|min:1',
            'has_medical_insurance_subscription'  => 'nullable|integer|in:0,1',
            'type_allowance_code'                 => 'nullable|string|in:constant,saving',
        ];
    }
}
