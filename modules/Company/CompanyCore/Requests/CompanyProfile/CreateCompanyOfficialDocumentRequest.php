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
            "file"=>"required|file|max:2048",
            "document_type_id"=>"required|exists:company_registration_types,id",
            "description"=>"required",
            "document_number"=>"required|numeric",
            "start_date"=>"required|date|before_or_equal:end_date",
            "end_date"=>"required|date|after_or_equal:start_date",
            "notification_date"=>"required|date|after_or_equal:start_date|before:end_date,-7 days",



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
            file: $this->file("file")
        );
    }
}

