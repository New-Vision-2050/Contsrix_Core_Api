<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\Rules\RequiredRegistrationNumber;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

class CreateCompanyLegalDataRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            'registration_type_id' => 'nullable|exists:company_registration_types,id',
            'regestration_number' => [
                'nullable',
                'string',
                new RequiredRegistrationNumber($this->input('registration_type_id')),
            ],
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'file' => 'nullable|array',
            'file.*' => 'mimes:pdf,jpeg,jpg,png,doc,docx',
        ];
    }

    public function messages(): array
    {
        return [
            'registration_type_id.exists' => __('validation.company_legal.registration_type_exists'),
            'start_date.date' => __('validation.company_legal.start_date_invalid'),
            'start_date.before_or_equal' => __('validation.company_legal.start_date_before_end'),
            'end_date.date' => __('validation.company_legal.end_date_invalid'),
            'end_date.after_or_equal' => __('validation.company_legal.end_date_after_start'),
            'file.*.mimes' => __('validation.company_legal.file_mimes'),
        ];
    }

    public function createCreateCompanyLegalDataDTO(): CreateCompanyLegalDataDTO
    {
        [ $company , $branch ] = $this->declareCompanyAndBranchUsingRequest();

        return new CreateCompanyLegalDataDTO(
            managementHierarchy: $branch,
            registrationTypeId: $this->filled('registration_type_id') ? Uuid::fromString($this->registration_type_id) : null,
            registrationNumber: $this->regestration_number,
            startDate: $this->start_date ? Carbon::parse($this->start_date)->format('Y-m-d') : null,
            endDate: $this->end_date ? Carbon::parse($this->end_date)->format('Y-m-d') : null,
            file: $this->file('file'),
        );
    }
}
