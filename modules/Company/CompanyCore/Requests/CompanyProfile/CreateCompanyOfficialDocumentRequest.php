<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyOfficialDocumentDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Ramsey\Uuid\Uuid;

class CreateCompanyOfficialDocumentRequest extends FormRequest
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function rules(): array
    {
        return [
            "name"=>"required",
            "files"=>"required|array",
            "files.*"=>"required|file|mimes:pdf,jpeg,jpg,png,doc,docx",
            "document_type_id"=>"required|exists:document_types,id",
            "description"=>"nullable",
            "document_number"=>"required",
            "start_date"=>"required|date|before_or_equal:end_date|date_format:Y-m-d",
            "end_date"=>"required|date|after_or_equal:start_date|date_format:Y-m-d",
            "notification_date"=>[
                "required",
                "date",
                "after_or_equal:start_date",
                "before:end_date",
                "date_format:Y-m-d",
                function ($attribute, $value, $fail) {
                    $endDate = \Carbon\Carbon::parse(request('end_date'));
                    $notificationDate = \Carbon\Carbon::parse($value);

                    if ($notificationDate->diffInDays($endDate) < 7) {
                        $fail('The notification date must be at least 7 days before the end date.');
                    }
                },
            ],
        ];
    }
    public function messages(): array
    {
        return [
            // English
            'files.required' => __('validation.custom.files.required'),
            'files.array' => __('validation.custom.files.array'),
            'files.*.required' => __('validation.custom.files.*.required'),
            'files.*.file' => __('validation.custom.files.*.file'),
            'files.*.mimes' => __('validation.custom.files.*.mimes'),

            'document_type_id.required' => __('validation.custom.document_type_id.required'),
            'document_type_id.exists' => __('validation.custom.document_type_id.exists'),

            'description.required' => __('validation.custom.description.required'),
            'document_number.required' => __('validation.custom.document_number.required'),
            'document_number.numeric' => __('validation.custom.document_number.numeric'),

            'start_date.required' => __('validation.custom.start_date.required'),
            'start_date.date' => __('validation.custom.start_date.date'),
            'start_date.before_or_equal' => __('validation.custom.start_date.before_or_equal'),
            'start_date.date_format' => __('validation.custom.start_date.date_format'),

            'end_date.required' => __('validation.custom.end_date.required'),
            'end_date.date' => __('validation.custom.end_date.date'),
            'end_date.after_or_equal' => __('validation.custom.end_date.after_or_equal'),
            'end_date.date_format' => __('validation.custom.end_date.date_format'),

            'notification_date.required' => __('validation.custom.notification_date.required'),
            'notification_date.date' => __('validation.custom.notification_date.date'),
            'notification_date.after_or_equal' => __('validation.custom.notification_date.after_or_equal'),
            'notification_date.before' => __('validation.custom.notification_date.before'),
            'notification_date.date_format' => __('validation.custom.notification_date.date_format'),

            'notification_date_7_days' => __('validation.notification_date_7_days'),
        ];
    }
    public function createCreateCompanyOfficialDocumentDTO(): CreateCompanyOfficialDocumentDTO
    {
        [ $company , $branch]= $this->declareCompanyAndBranchUsingRequest();

        return new CreateCompanyOfficialDocumentDTO(
            managementHierarchy: $branch,
            name: $this->name,
            description: $this->description,
            documentNumber: $this->document_number,
            startDate: $this->start_date,
            endDate: $this->end_date,
            notificationDate: $this->notification_date,
            documentTypeId: Uuid::fromString($this->document_type_id),
            files: $this->file("files")
        );
    }
}

