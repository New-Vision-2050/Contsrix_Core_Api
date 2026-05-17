<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMedicalInsuranceSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
