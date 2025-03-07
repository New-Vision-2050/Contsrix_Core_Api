<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Support\Collection;
use Modules\AdminRequest\Repositories\AdminRequestRepository;
use Modules\Company\CompanyCore\DTO\CompanyProfile\UpdateOfficialCompanyDataRequestDTO;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;
use Modules\Company\CompanyCore\Jobs\CheckCompanyActivity;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CompanyProfileService
{
    public function __construct(
        private AdminRequestRepository $adminRequestRepository,
    ) {
    }

    public function updateCompanyProfileRequest(UpdateOfficialCompanyDataRequestDTO $companyDataRequestDTO)
    {
        $adminRequest = $this->adminRequestRepository->createAdminRequestForCompanyOfficialData(
            userId: auth()->user()->id,
            data: ["id"=>$companyDataRequestDTO->getId(),"data"=>$companyDataRequestDTO->toArray()],
            requestType: "companyOfficialDataUpdate",
            action:["ar"=>"طلب تعديل البيانات الرسميه للشركة","en"=>"Company official data update request"] ,
            notes: $companyDataRequestDTO->getNotes()

        );

        return $adminRequest;

    }


}
