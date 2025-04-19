<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Commands\CompanyProfile\DeleteCompanyOfficialDocumentMediaCommand;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyLegalDataCommand;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyOfficialDocumentCommand;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyLegalDataRepository;
use Modules\Company\CompanyCore\Repositories\CompanyOfficialDocumentRepository;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyLegalDataRequest;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyOfficialDocumentMediaHandler
{
    public function __construct(
        private CompanyOfficialDocumentRepository $repository,
    )
    {
    }

    public function handle(UuidInterface $id , $mediaId)
    {

        return $this->repository->deleteMedia($id , $mediaId);

    }
}
