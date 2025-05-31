<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Commands\CompanyProfile\SetCompanyAddressCommand;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyAddressRepository;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class UpdateCompanySetAddressHandler
{
    public function __construct(
        private CompanyAddressRepository             $repository,
    )
    {
    }

    public function handle(SetCompanyAddressCommand $setCompanyAddressCommand)
    {

        $this->repository->updateCompanyAddress($setCompanyAddressCommand->getId() , $setCompanyAddressCommand->toArray(),$setCompanyAddressCommand->latAndLongToArray());
    }
}
