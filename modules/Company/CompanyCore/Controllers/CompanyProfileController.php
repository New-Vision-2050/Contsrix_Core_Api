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
use Modules\Company\CompanyCore\Handlers\CompanyProfile\DeleteCompanyOfficialDocumentHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\DeleteCompanyOfficialDocumentMediaHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateCompanyLegalDataHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateCompanyOfficialDocumentHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateCompanySetAddressHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateOfficialCompanyDataHandler;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Requests\CompanyProfile\CreateCompanyLegalDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\CreateCompanyOfficialDocumentRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\getLocationByLatLongRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\SetCompanyAddressRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\SetCompanyLogoRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\RequestUpdateLegalCompanyDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyLegalDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyOfficialDocumentRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyData;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyDataRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyProfileService;
use Modules\Country\Models\Country;
use Modules\Country\Presenters\CountryStateCityPresenter;
use Ramsey\Uuid\Uuid;

class CompanyProfileController extends Controller
{
    public function __construct(
        private UpdateOfficialCompanyDataHandler          $updateOfficialCompanyDataHandler,
        private CompanyCRUDService                        $companyService,
        private CompanyProfileService                     $companyProfileService,
        private UpdateCompanyLegalDataHandler             $updateCompanyLegalDataHandler,
        private UpdateCompanyOfficialDocumentHandler      $updateCompanyOfficialDocumentHandler,
        private DeleteCompanyOfficialDocumentHandler      $deleteCompanyOfficialDocumentHandler,
        private DeleteCompanyOfficialDocumentMediaHandler $deleteCompanyOfficialDocumentMediaHandler,
        private UpdateCompanySetAddressHandler            $updateCompanySetAddressHandler
    )
    {
    }


    public function updateOfficialData(UpdateOfficialCompanyData $request)
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

        [$country,
            $state,
            $city,
            $neighborhood,
            $postalCode,
            $route] = $this->companyProfileService->geoCoding($geoCodingDTO);
        return Json::item((new CountryStateCityPresenter($country,$state,$city,$neighborhood,$postalCode,$route))->getData());
    }

    public function setCompanyLogo(setCompanyLogoRequest $request)
    {
        $logo = $request->createAssignLogoToCompanyDTO();
        $this->companyProfileService->assignLogo($logo);
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        $presenter = new CompanyPresenter($company);

        return Json::item($presenter->getData());
    }

    public function validateCompanyLogo(Request $request)
    {
        $logo = $request->logo;
        $validations = $this->companyProfileService->validateLogo($logo);
        return Json::item($validations);
    }

    public function createLegalData(CreateCompanyLegalDataRequest $request)
    {
        $this->companyProfileService->createCompanyLegalData($request->createCreateCompanyLegalDataDTO());
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());

    }

    public function requestUpdateLegalDataRequest(RequestUpdateLegalCompanyDataRequest $request)
    {
        $legalDataRequest = $this->companyProfileService->updateLegalDataRequest($request->createUpdateLegalCompanyDataRequestDTO());
        return Json::item((new AdminRequestPresenter($legalDataRequest))->getData());
    }

    public function setAddress(SetCompanyAddressRequest $request)
    {
        $command = $request->createSetCompanyAddressCommand();
        $this->updateCompanySetAddressHandler->handle($command);

        $company = $this->companyService->getCurrentCompanyLoggedIn();

        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function updateCompanyLegalData(UpdateCompanyLegalDataRequest $request)
    {
        $command = $request->createUpdateLegalCompanyDataCommand();
        $this->updateCompanyLegalDataHandler->handle($command);
        $this->companyProfileService->getCompanyLegalData(Uuid::fromString($request->route("id")));
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function createOfficialDocument(CreateCompanyOfficialDocumentRequest $request)
    {
        $this->companyProfileService->createCompanyOfficialDocument($request->createCreateCompanyOfficialDocumentDTO());
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function updateOfficialDocument(UpdateCompanyOfficialDocumentRequest $request)
    {
        $command = $request->createUpdateCompanyOfficialDocumentCommand();
        $this->updateCompanyOfficialDocumentHandler->handle($command);
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }


    public function deleteOfficialDocument(Request $request)
    {

        $this->deleteCompanyOfficialDocumentHandler->handle(Uuid::fromString($request->route("id")));
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function deleteOfficialDocumentMedia(Request $request)
    {

        $this->deleteCompanyOfficialDocumentMediaHandler->handle(Uuid::fromString($request->route("id")), Uuid::fromString($request->route("media_id")));
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

}
