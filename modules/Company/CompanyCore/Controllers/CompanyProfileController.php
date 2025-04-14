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
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateOfficialCompanyDataHandler;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Requests\CompanyProfile\CreateCompanyLegalDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\CreateCompanyOfficialDocumentRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\getLocationByLatLongRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\SetCompanyLogoRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\RequestUpdateLegalCompanyDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyLegalDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyOfficialDocumentRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyData;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyDataRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyProfileService;
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
        private DeleteCompanyOfficialDocumentMediaHandler $deleteCompanyOfficialDocumentMediaHandler
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

    public function validateCompanyLogo(Request $request)
    {
        $logo = $request->logo;
        $validations = $this->companyProfileService->validateLogo($logo);
        return Json::item($validations);
    }

    public function createLegalData(CreateCompanyLegalDataRequest $request)
    {
        $companyLegalData = $this->companyProfileService->createCompanyLegalData($request->createCreateCompanyLegalDataDTO());
        $company = $this->companyService->get($companyLegalData->company_id);
        return Json::item((new CompanyPresenter($company))->getData());

    }

    public function requestUpdateLegalDataRequest(RequestUpdateLegalCompanyDataRequest $request)
    {
        $legalDataRequest = $this->companyProfileService->updateLegalDataRequest($request->createUpdateLegalCompanyDataRequestDTO());
        return Json::item((new AdminRequestPresenter($legalDataRequest))->getData());
    }

    public function setAddress()
    {

    }

    public function updateCompanyLegalData(UpdateCompanyLegalDataRequest $request)
    {
        $command = $request->createUpdateLegalCompanyDataCommand();
        $this->updateCompanyLegalDataHandler->handle($command);

        $companyLegalData = $this->companyProfileService->getCompanyLegalData(Uuid::fromString($request->route("id")));
        $company = $this->companyService->get(Uuid::fromString($companyLegalData->company_id));
        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function createOfficialDocument(CreateCompanyOfficialDocumentRequest $request)
    {
        $this->companyProfileService->createCompanyOfficialDocument($request->createCreateCompanyOfficialDocumentDTO());
        $company = $this->companyService->get($request->createCreateCompanyOfficialDocumentDTO()->getId());
        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function updateOfficialDocument(UpdateCompanyOfficialDocumentRequest $request)
    {
        $command = $request->createUpdateCompanyOfficialDocumentCommand();
        $this->updateCompanyOfficialDocumentHandler->handle($command);
        $companyOfficial = $this->companyProfileService->getCompanyOfficialDocument($command->getId());
        $company = $this->companyService->get($companyOfficial->company_id);
        return Json::item((new CompanyPresenter($company))->getData());
    }


    public function deleteOfficialDocument(Request $request)
    {
        $companyOfficial = $this->companyProfileService->getCompanyOfficialDocument(Uuid::fromString($request->route("id")));
        $company = $this->companyService->get($companyOfficial->company_id);
        $this->deleteCompanyOfficialDocumentHandler->handle(Uuid::fromString($request->route("id")));
        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function deleteOfficialDocumentMedia(Request $request)
    {
        $companyOfficial = $this->companyProfileService->getCompanyOfficialDocument(Uuid::fromString($request->route("id")));
        $company = $this->companyService->get($companyOfficial->company_id);
        $this->deleteCompanyOfficialDocumentMediaHandler->handle(Uuid::fromString($request->route("id")), Uuid::fromString($request->route("media_id")));
        return Json::item((new CompanyPresenter($company))->getData());
    }

}
