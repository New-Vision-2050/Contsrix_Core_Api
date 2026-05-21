<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMedicalInsuranceSubscriptionListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'                 => 'integer|min:1',
            'per_page'             => 'integer|min:1',
            'user_id'              => 'nullable|uuid|exists:users,id',
            'user_ids'             => 'nullable|array',
            'user_ids.*'           => 'uuid|exists:users,id',
            'medical_insurance_id' => 'nullable|uuid|exists:medical_insurances,id',
            'status'               => 'nullable|integer|in:-1,0,1',
        ];
    }
}
