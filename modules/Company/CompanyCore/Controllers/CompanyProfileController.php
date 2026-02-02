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
use Modules\Company\CompanyCore\Handlers\CompanyProfile\DeleteCompanyLegalDataHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\DeleteCompanyOfficialDocumentHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\DeleteCompanyOfficialDocumentMediaHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateCompanyLegalDataHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateCompanyOfficialDocumentHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateCompanySetAddressHandler;
use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateOfficialCompanyDataHandler;
use Modules\Company\CompanyCore\Presenters\AddressPresenter;
use Modules\Company\CompanyCore\Presenters\CompanyLegalDataPresenter;
use Modules\Company\CompanyCore\Presenters\CompanyOfficialDocumentPresenter;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\CompanyCore\Requests\CompanyProfile\CreateCompanyLegalDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\CreateCompanyOfficialDocumentRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\GetLocationByLatLongRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\SetCompanyAddressRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\SetCompanyLogoRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\RequestUpdateLegalCompanyDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyLegalDataRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyOfficialDocumentRequest;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyData;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyDataRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyProfileService;
use Modules\Country\Presenters\CountryStateCityPresenter;
use Ramsey\Uuid\Uuid;

class CompanyProfileController extends Controller
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;


    public function __construct(
        private UpdateOfficialCompanyDataHandler          $updateOfficialCompanyDataHandler,
        private CompanyCRUDService                        $companyService,
        private CompanyProfileService                     $companyProfileService,
        private UpdateCompanyLegalDataHandler             $updateCompanyLegalDataHandler,
        private UpdateCompanyOfficialDocumentHandler      $updateCompanyOfficialDocumentHandler,
        private DeleteCompanyOfficialDocumentHandler      $deleteCompanyOfficialDocumentHandler,
        private DeleteCompanyOfficialDocumentMediaHandler $deleteCompanyOfficialDocumentMediaHandler,
        private UpdateCompanySetAddressHandler            $updateCompanySetAddressHandler,
        private DeleteCompanyLegalDataHandler             $deleteCompanyLegalDataHandler,
    )
    {
    }

    /**
     * @param UpdateOfficialCompanyData $request
     * @return JsonResponse
     * @throws \Exception
     */

    public function updateOfficialData(UpdateOfficialCompanyData $request)
    {
        $command = $request->createUpdateOfficialCompanyDataCommand();
        $this->updateOfficialCompanyDataHandler->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * @param UpdateOfficialCompanyDataRequest $request
     * @return JsonResponse
     */

    public function updateOfficialDataRequest(UpdateOfficialCompanyDataRequest $request)
    {
        $adminRequest = $this->companyProfileService->updateCompanyProfileRequest($request->createUpdateOfficialCompanyDataRequestDTO());

        return Json::item((new AdminRequestPresenter($adminRequest))->getData());
    }

    /**
     * @param GetLocationByLatLongRequest $request
     * @return JsonResponse
     */
    public function getAddressFromMap(getLocationByLatLongRequest $request)
    {
        try {
            $geoCodingDTO = $request->createGeoCodingDTO();

            [
                $country,
                $state,
                $city,
                $neighborhood,
                $postalCode,
                $route,
                $aiSupported
            ] = $this->companyProfileService->geoCoding($geoCodingDTO);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(),httpStatus:  $e->getCode());

        }

        return Json::item((new CountryStateCityPresenter($country,$state,$city,$neighborhood,$postalCode,$route,$aiSupported))->getData());
    }

    /**
     * @param SetCompanyLogoRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function setCompanyLogo(setCompanyLogoRequest $request)
    {
        $logo = $request->createAssignLogoToCompanyDTO();
        $this->companyProfileService->assignLogo($logo);

        $company = $this->companyService->getCurrentCompanyLoggedIn();
        $presenter = new CompanyPresenter($company);

        return Json::item($presenter->getData());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function validateCompanyLogo(Request $request)
    {
        $logo = $request->logo;
        [$validations,$flag] = $this->companyProfileService->validateLogo($logo);
        if($flag)
        {
            return Json::item($validations);
        }else{
            return Json::error("Invalid Logo",httpStatus: 422,data: $validations);
        }
    }

    /**
     * @param CreateCompanyLegalDataRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createLegalData(CreateCompanyLegalDataRequest $request)
    {
        $legalDataDTOs = $request->createCreateCompanyLegalDataDTOs();
       return $this->companyProfileService->createMultipleCompanyLegalData($legalDataDTOs);

        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());

    }

    /**
     * @param RequestUpdateLegalCompanyDataRequest $request
     * @return JsonResponse
     * @throws \Exception
     */

    public function requestUpdateLegalDataRequest(RequestUpdateLegalCompanyDataRequest $request)
    {
        $legalDataRequest = $this->companyProfileService->updateLegalDataRequest($request->createUpdateLegalCompanyDataRequestDTO());
        return Json::item((new AdminRequestPresenter($legalDataRequest))->getData());
    }

    /**
     * @param SetCompanyAddressRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function setAddress(SetCompanyAddressRequest $request)
    {
        $command = $request->createSetCompanyAddressCommand();
        $this->updateCompanySetAddressHandler->handle($command);

        $company = $this->companyService->getCurrentCompanyLoggedIn();

        return Json::item((new CompanyPresenter($company))->getData());
    }

    /**
     * @param UpdateCompanyLegalDataRequest $request
     * @return JsonResponse
     */
    public function updateCompanyLegalData(UpdateCompanyLegalDataRequest $request)
    {
        $command = $request->createUpdateLegalCompanyDataCommand();
        $this->updateCompanyLegalDataHandler->handle($command);

        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

    /**
     * @param CreateCompanyOfficialDocumentRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createOfficialDocument(CreateCompanyOfficialDocumentRequest $request)
    {
        $this->companyProfileService->createCompanyOfficialDocument($request->createCreateCompanyOfficialDocumentDTO());
        $company = $this->companyService->getCurrentCompanyLoggedIn();

        return Json::item((new CompanyPresenter($company))->getData());
    }

    /**
     * @param UpdateCompanyOfficialDocumentRequest $request
     * @return JsonResponse
     */
    public function updateOfficialDocument(UpdateCompanyOfficialDocumentRequest $request)
    {
        $command = $request->createUpdateCompanyOfficialDocumentCommand();
        $this->updateCompanyOfficialDocumentHandler->handle($command);

        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOfficialDocument(Request $request)
    {

        $this->deleteCompanyOfficialDocumentHandler->handle(Uuid::fromString($request->route("id")));

        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOfficialDocumentMedia(Request $request)
    {

        $this->deleteCompanyOfficialDocumentMediaHandler->handle(Uuid::fromString($request->route("id")), $request->route("media_id"));
        $company = $this->companyService->getCurrentCompanyLoggedIn();
        return Json::item((new CompanyPresenter($company))->getData());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompanyLegalData(): JsonResponse
    {
        $legalData = $this->companyProfileService->getCompanyLegalDataForCompany();
        return Json::item(CompanyLegalDataPresenter::collection($legalData));
    }

    /**
     * Get company address as a separate API endpoint
     *
     * @return JsonResponse
     */
    public function getCompanyAddress(): JsonResponse
    {
        $address = $this->companyProfileService->getCompanyAddressForCompany();
        return Json::item((new AddressPresenter($address))->getData());
    }

    /**
     * Get company official documents as a separate API endpoint
     *
     * @return JsonResponse
     */
    public function getCompanyOfficialDocuments(): JsonResponse
    {
        $officialDocuments = $this->companyProfileService->getCompanyOfficialDocumentsForCompany();
        return Json::items(CompanyOfficialDocumentPresenter::collection($officialDocuments));
    }

    /**
     * Get company branches as a separate API endpoint
     *
     * @return JsonResponse
     */
    public function getCompanyBranches(): JsonResponse
    {
        $branches = $this->companyProfileService->getCompanyBranchesForCompany();
        return Json::items(ManagementHierarchyPresenter::collection($branches));
    }
}
