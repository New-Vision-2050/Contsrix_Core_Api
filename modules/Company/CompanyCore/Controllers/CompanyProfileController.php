<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\AdminRequest\Presenters\AdminRequestPresenter;
use Modules\Company\CompanyCore\DTO\CompanyProfile\CreateCompanyLegalDataDTO;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateOfficialCompanyDataHandler;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Requests\CompanyProfile\CreateCompanyLegalDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\getLocationByLatLongRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\SetCompanyLogoRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateLegalCompanyDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyData;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyDataRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyProfileService;
use Ramsey\Uuid\Uuid;

class CompanyProfileController extends Controller
{
    public function __construct(
        private UpdateOfficialCompanyDataHandler $updateOfficialCompanyDataHandler,
        private CompanyCRUDService               $companyService,
        private CompanyProfileService            $companyProfileService
    )
    {
    }


    public function updateOfficialData(UpdateOfficialCompanyData $request): JsonResponse
    {
        $command = $request->createUpdateOfficialCompanyDataCommand();
        $this->updateOfficialCompanyDataHandler->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function updateOfficialDataRequest(UpdateOfficialCompanyDataRequest $request)
    {
        $adminRequest = $this->companyProfileService->updateCompanyProfileRequest($request->createUpdateOfficialCompanyDataRequestDTO());

        return Json::item((new AdminRequestPresenter($adminRequest))->getData());
    }

    public function getAddressFromMap(getLocationByLatLongRequest $request)
    {
        $geoCodingDTO = $request->createGeoCodingDTO();
        $result = $this->companyProfileService->geoCoding($geoCodingDTO);
        return Json::item($result);
    }

    public function setCompanyLogo(setCompanyLogoRequest $request)
    {
        $logo = $request->createAssignLogoToCompanyDTO();
        $company = $this->companyProfileService->assignLogo($logo);
        $presenter = new CompanyPresenter($company);

        return Json::item($presenter->getData());
    }

    public function validateCompanyLogo(setCompanyLogoRequest $request)
    {
        $logo = $request->createAssignLogoToCompanyDTO();
        $validations = $this->companyProfileService->validateLogo($logo);
        return Json::item($validations);
    }

    public function createLegalData(CreateCompanyLegalDataRequest $request)
    {
        $companyLegalData = $this->companyProfileService->createCompanyLegalData($request->createCreateCompanyLegalDataDTO());
        $company = $this->companyService->get($companyLegalData->company_id);
        return Json::item((new CompanyPresenter($company))->getData());

    }

    public function updateLegalDataRequest(UpdateLegalCompanyDataRequest $request)
    {
        $legalDataRequest = $this->companyProfileService->updateLegalDataRequest($request->createUpdateLegalCompanyDataRequestDTO());
        return Json::item((new AdminRequestPresenter($legalDataRequest))->getData());
    }

    public function setAddress()
    {

    }

}
