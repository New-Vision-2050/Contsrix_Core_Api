<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyOfficialDocumentCommand;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyOfficialDocumentDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\RequestUpdateLegalCompanyDataRequestDTO;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Ramsey\Uuid\Uuid;

class DeleteCompanyOfficialDocumentMediaRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            "file_"
        ];
    }

    public function createUpdateCompanyOfficialDocumentCommand(): UpdateCompanyOfficialDocumentCommand
    {
        return new UpdateCompanyOfficialDocumentCommand(
            id: Uuid::fromString($this->route("id")),
            name: $this->name,
            description: $this->description,
            documentNumber: $this->document_number,
            startDate: $this->start_date,
            endDate: $this->end_date,
            notificationDate: $this->notification_date,
            documentTypeId: Uuid::fromString($this->document_type_id),
            files: $this->file("files"),
            filesDeleteIds: $this->file("files_deleted"),
        );
    }
}

