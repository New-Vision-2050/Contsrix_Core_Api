<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class UpdateLegalCompanyDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'registration_type_id' => 'required|exists:company_registration_types,id',
            'regestration_no' => 'required',
            'registration_no_start_date' => 'required|date|before_or_equal:registration_no_end_date',
            'registration_no_end_date' => 'required|date|after_or_equal:registration_no_start_date',
            "file"=>"required|mimes:pdf,jpeg,jpg,png,doc,docx",
        ];
    }

    public function createUpdateLegalCompanyDataRequestDTO(): UpdateLegalCompanyDataRequestDTO
    {
        return new UpdateLegalCompanyDataRequestDTO(
            id: Uuid::fromString($this->route('id')),
            registrationTypeId:Uuid::fromString($this->registration_type_id),
            registrationNo: $this->regestration_no,
            registrationNoStartDate: $this->registration_no_start_date,
            registrationNoEndDate: $this->registration_no_end_date,
            file: $this->file("file"),
        );
    }
}

