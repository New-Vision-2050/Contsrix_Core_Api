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
            'regestration_number' => 'required',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'file' => 'required|array',
            'file.*' => 'mimes:pdf,jpeg,jpg,png,doc,docx',
        ];
    }
    public function messages(): array
    {
        return [
            'registration_type_id.required' => __('validation.company_legal.registration_type_required'),
            'registration_type_id.exists' => __('validation.company_legal.registration_type_exists'),
            'registration_number.required' => __('validation.company_legal.registration_number_required'),
            'start_date.required' => __('validation.company_legal.start_date_required'),
            'start_date.date' => __('validation.company_legal.start_date_invalid'),
            'start_date.before_or_equal' => __('validation.company_legal.start_date_before_end'),
            'end_date.required' => __('validation.company_legal.end_date_required'),
            'end_date.date' => __('validation.company_legal.end_date_invalid'),
            'end_date.after_or_equal' => __('validation.company_legal.end_date_after_start'),
            'file.required' => __('validation.company_legal.file_required'),
            'file.*.mimes' => __('validation.company_legal.file_mimes'),
        ];
    }
    public function createCreateCompanyLegalDataDTO(): CreateCompanyLegalDataDTO
    {
      [ $company , $branch]= $this->declareCompanyAndBranchUsingRequest();
        return new CreateCompanyLegalDataDTO(
            managementHierarchy: $branch,
            registrationTypeId:Uuid::fromString($this->registration_type_id),
            registrationNumber: $this->regestration_number,
            startDate: $this->start_date,
            endDate: $this->end_date,
            file: $this->file("file"),
        );
    }
}

