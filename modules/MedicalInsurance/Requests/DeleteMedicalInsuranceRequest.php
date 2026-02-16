<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteMedicalInsuranceRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
