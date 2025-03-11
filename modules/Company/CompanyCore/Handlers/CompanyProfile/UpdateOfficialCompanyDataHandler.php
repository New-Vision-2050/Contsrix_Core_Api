<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class UpdateOfficialCompanyDataHandler
{
    public function __construct(
        private CompanyRepository             $repository,
        private ManagementHierarchyRepository $managementHierarchyRepository,
    )
    {
    }

    public function handle(UpdateOfficialCompanyDataCommand $updateOfficialCompanyDataCommand)
    {
        try {
            DB::beginTransaction();
            $this->repository->updateCompany($updateOfficialCompanyDataCommand->getId(), $updateOfficialCompanyDataCommand->toArray());
            $this->managementHierarchyRepository->getMainBranchForCompany($updateOfficialCompanyDataCommand->getId())->update(["name" => $updateOfficialCompanyDataCommand->getBranchName()]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
