<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class RequestUpdateLegalCompanyDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [

            'data.*.id' => 'required|exists:company_legal_data,id',
            'data.*.registration_type_id' => 'required|exists:company_registration_types,id',
            'data.*.regestration_number' => 'required',
        ];
    }

    public function createUpdateLegalCompanyDataRequestDTO(): RequestUpdateLegalCompanyDataRequestDTO
    {
        return new RequestUpdateLegalCompanyDataRequestDTO(
            id: Uuid::fromString(tenant("id")),
            data:$this->data
        );
    }
}

