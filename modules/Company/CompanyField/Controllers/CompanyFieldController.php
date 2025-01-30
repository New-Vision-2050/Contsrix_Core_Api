<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyField\Handlers\DeleteCompanyFieldHandler;
use Modules\Company\CompanyField\Handlers\UpdateCompanyFieldHandler;
use Modules\Company\CompanyField\Presenters\CompanyFieldPresenter;
use Modules\Company\CompanyField\Requests\CreateCompanyFieldRequest;
use Modules\Company\CompanyField\Requests\DeleteCompanyFieldRequest;
use Modules\Company\CompanyField\Requests\GetCompanyFieldListRequest;
use Modules\Company\CompanyField\Requests\GetCompanyFieldRequest;
use Modules\Company\CompanyField\Requests\UpdateCompanyFieldRequest;
use Modules\Company\CompanyField\Services\CompanyFieldCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyFieldController extends Controller
{
    public function __construct(
        private CompanyFieldCRUDService $companyFieldService,
        private UpdateCompanyFieldHandler $updateCompanyFieldHandler,
        private DeleteCompanyFieldHandler $deleteCompanyFieldHandler,
    ) {
    }

    public function index(GetCompanyFieldListRequest $request): JsonResponse
    {
        $list = $this->companyFieldService->all();

        return Json::buildItems(null,['company_fields' => CompanyFieldPresenter::collection($list)]);
    }

    public function show(GetCompanyFieldRequest $request): JsonResponse
    {
        $item = $this->companyFieldService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyFieldPresenter($item);

        return Json::buildItems('company_field', $presenter->getData());
    }

    public function store(CreateCompanyFieldRequest $request): JsonResponse
    {
        $createdItem = $this->companyFieldService->create($request->createCreateCompanyFieldDTO());

        $presenter = new CompanyFieldPresenter($createdItem);

        return Json::buildItems('company_field', $presenter->getData());
    }

    public function update(UpdateCompanyFieldRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyFieldCommand();
        $this->updateCompanyFieldHandler->handle($command);

        $item = $this->companyFieldService->get($command->getId());

        $presenter = new CompanyFieldPresenter($item);

        return Json::buildItems('company_field', $presenter->getData());
    }

    public function delete(DeleteCompanyFieldRequest $request): JsonResponse
    {
        $this->deleteCompanyFieldHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
