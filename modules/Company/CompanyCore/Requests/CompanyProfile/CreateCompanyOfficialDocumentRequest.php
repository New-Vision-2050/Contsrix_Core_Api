<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyOfficialDocumentDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class CreateCompanyOfficialDocumentRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            "name"=>"required",
            "files"=>"required|array",
            "files.*"=>"required|file|mimes:pdf,jpeg,jpg,png,doc,docx",
            "document_type_id"=>"required|exists:company_registration_types,id",
            "description"=>"required",
            "document_number"=>"required|numeric",
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

    public function createCreateCompanyOfficialDocumentDTO(): CreateCompanyOfficialDocumentDTO
    {
        return new CreateCompanyOfficialDocumentDTO(
            id: Uuid::fromString(tenant("id")),
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

