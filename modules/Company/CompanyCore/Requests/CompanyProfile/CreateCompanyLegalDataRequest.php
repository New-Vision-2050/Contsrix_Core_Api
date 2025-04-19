<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;

class CreateCompanyLegalDataRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function rules(): array
    {
        return [
            'registration_type_id' => 'required|exists:company_registration_types,id',
            'registration_number' => 'required',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            "file"=>"required|mimes:pdf,jpeg,jpg,png,doc,docx",
        ];
    }

    public function createCreateCompanyLegalDataDTO(): CreateCompanyLegalDataDTO
    {
      [ $company , $branch]= $this->declareCompanyAndBranchUsingRequest();
        return new CreateCompanyLegalDataDTO(
            managementHierarchy: $branch,
            registrationTypeId:Uuid::fromString($this->registration_type_id),
            registrationNumber: $this->registration_number,
            startDate: $this->start_date,
            endDate: $this->end_date,
            file: $this->file("file"),
        );
    }
}

