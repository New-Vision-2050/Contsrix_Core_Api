<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyLegalDataCommand;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyOfficialDocumentCommand;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyLegalDataRepository;
use Modules\Company\CompanyCore\Repositories\CompanyOfficialDocumentRepository;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyLegalDataRequest;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class UpdateCompanyOfficialDocumentHandler
{
    public function __construct(
        private CompanyOfficialDocumentRepository $repository,
    )
    {
    }

    public function handle(UpdateCompanyOfficialDocumentCommand $companyOfficialDocumentCommand)
    {

        return $this->repository->updateCompanyOfficialDocument($companyOfficialDocumentCommand->getId(), $companyOfficialDocumentCommand->toArray(), $companyOfficialDocumentCommand->getFiles(), $companyOfficialDocumentCommand->getDeletedFilesId());

    }
}
