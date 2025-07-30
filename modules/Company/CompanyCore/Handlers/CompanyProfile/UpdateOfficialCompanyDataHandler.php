<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Subscription\Package\Services\PackageAssignmentService;

class UpdateOfficialCompanyDataHandler
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function __construct(
        private CompanyRepository             $repository,
        private PackageAssignmentService      $packageAssignmentService,
        private ManagementHierarchyRepository $managementHierarchyRepository,
    )
    {
    }

    public function handle(UpdateOfficialCompanyDataCommand $updateOfficialCompanyDataCommand)
    {


        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        try {
            DB::beginTransaction();
            $this->repository->updateCompany($updateOfficialCompanyDataCommand->getId(), $updateOfficialCompanyDataCommand->toArray());

            $this->packageAssignmentService->assignPackagesToCompany((string)$updateOfficialCompanyDataCommand->getId() , $updateOfficialCompanyDataCommand->packages());


            $this->managementHierarchyRepository->updateWhere(["id" => $branch->id], [
                "name" => $updateOfficialCompanyDataCommand->getBranchName(),
                "phone" => $updateOfficialCompanyDataCommand->getPhone(),
                "email" => $updateOfficialCompanyDataCommand->getEmail(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"), 500);
        }
    }
}
