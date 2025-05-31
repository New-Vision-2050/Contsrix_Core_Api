<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;

class UpdateOfficialCompanyDataRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;
    public function rules(): array
    {
        return [
            'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'country_id' => 'required|exists:countries,id',
            'company_type_id' => 'required|exists:company_types,id',
            'company_field_id' => 'required|exists:company_fields,id',
            'notes' => 'present|nullable|string',
            "file"=>"nullable|array",
            'file.*' => 'mimes:pdf,jpeg,jpg,png,doc,docx',

        ];
    }

    public function createUpdateOfficialCompanyDataRequestDTO(): UpdateOfficialCompanyDataRequestDTO
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        return new UpdateOfficialCompanyDataRequestDTO(
            id: Uuid::fromString($company->id),
            name: $this->get('name'),
            countryId: (string)$this->get('country_id'),
            companyTypeId: (string)$this->get('company_type_id'),
            companyFieldId: (string)$this->get('company_field_id'),
            notes: $this->get('notes'),
            file: $this->file("file")
        );
    }
}

