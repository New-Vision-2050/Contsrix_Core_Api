<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyType\Handlers\DeleteCompanyTypeHandler;
use Modules\Company\CompanyType\Handlers\UpdateCompanyTypeHandler;
use Modules\Company\CompanyType\Presenters\CompanyTypePresenter;
use Modules\Company\CompanyType\Requests\CreateCompanyTypeRequest;
use Modules\Company\CompanyType\Requests\DeleteCompanyTypeRequest;
use Modules\Company\CompanyType\Requests\GetCompanyTypeListRequest;
use Modules\Company\CompanyType\Requests\GetCompanyTypeRequest;
use Modules\Company\CompanyType\Requests\UpdateCompanyTypeRequest;
use Modules\Company\CompanyType\Services\CompanyTypeCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyTypeController extends Controller
{
    public function __construct(
        private CompanyTypeCRUDService $companyTypeService,
        private UpdateCompanyTypeHandler $updateCompanyTypeHandler,
        private DeleteCompanyTypeHandler $deleteCompanyTypeHandler,
    ) {
    }

    public function index(GetCompanyTypeListRequest $request): JsonResponse
    {
        $list = $this->companyTypeService->all();

        return Json::buildItems(null,['company_types' => CompanyTypePresenter::collection($list)]);
    }

    public function show(GetCompanyTypeRequest $request): JsonResponse
    {
        $item = $this->companyTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyTypePresenter($item);

        return Json::buildItems('company_type', $presenter->getData());
    }

    public function store(CreateCompanyTypeRequest $request): JsonResponse
    {
        $createdItem = $this->companyTypeService->create($request->createCreateCompanyTypeDTO());

        $presenter = new CompanyTypePresenter($createdItem);

        return Json::buildItems('company_type', $presenter->getData());
    }

    public function update(UpdateCompanyTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyTypeCommand();
        $this->updateCompanyTypeHandler->handle($command);

        $item = $this->companyTypeService->get($command->getId());

        $presenter = new CompanyTypePresenter($item);

        return Json::buildItems('company_type', $presenter->getData());
    }

    public function delete(DeleteCompanyTypeRequest $request): JsonResponse
    {
        $this->deleteCompanyTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
