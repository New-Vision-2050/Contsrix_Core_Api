<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;


use Modules\Company\CompanyCore\Handlers\CompanyProfile\UpdateOfficialCompanyDataHandler;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateOfficialCompanyDataRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyProfileController extends Controller
{
    public function __construct(
        private UpdateOfficialCompanyDataHandler $updateOfficialCompanyDataHandler,
        private CompanyCRUDService               $companyService

    )
    {
    }


    public function update(UpdateOfficialCompanyDataRequest $request): JsonResponse
    {
        $command = $request->createUpdateOfficialCompanyDataCommand();
        $this->updateOfficialCompanyDataHandler->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item( $presenter->getData());
    }


}
