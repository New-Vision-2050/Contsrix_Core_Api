<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;

class CompanyOfficialDocumentPresenter extends AbstractPresenter
{
    private CompanyOfficialDocument $companyOfficialDocument;

    public function __construct(CompanyOfficialDocument $companyOfficialDocument)
    {
        $this->companyOfficialDocument = $companyOfficialDocument;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyOfficialDocument->id,
            'name' => $this->companyOfficialDocument->name,
            'files' => $this->companyOfficialDocument->getMedia("*")->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                    "name"=>$media->name,
                    "mime_type" => $media->mime_type,
                ];
            }),
            "description" => $this->companyOfficialDocument->description,
            "document_number" => $this->companyOfficialDocument->document_number,
            "start_date" => $this->companyOfficialDocument->start_date,
            "end_date" => $this->companyOfficialDocument->end_date,
            "notification_date" => $this->companyOfficialDocument->notification_date,
            "document_type" => $this->companyOfficialDocument->documentType->name,
            "document_type_id" => $this->companyOfficialDocument->document_type_id,
            "logs"=>ActivityLogPresenter::collection($this->companyOfficialDocument->activityLogs),
            'company_legal_data_id'=> $this->companyOfficialDocument->company_legal_data_id,
            'company_legal_data' => $this->companyOfficialDocument->companyLegalData ,
        ];
    }
}
