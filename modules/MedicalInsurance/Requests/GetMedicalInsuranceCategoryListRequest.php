<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMedicalInsuranceCategoryListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'     => 'integer',
            'per_page' => 'integer',
        ];
    }
}
