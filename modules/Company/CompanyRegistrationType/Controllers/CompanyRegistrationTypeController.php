<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyRegistrationType\Handlers\DeleteCompanyRegistrationTypeHandler;
use Modules\Company\CompanyRegistrationType\Handlers\UpdateCompanyRegistrationTypeHandler;
use Modules\Company\CompanyRegistrationType\Presenters\CompanyRegistrationTypePresenter;
use Modules\Company\CompanyRegistrationType\Requests\CreateCompanyRegistrationTypeRequest;
use Modules\Company\CompanyRegistrationType\Requests\DeleteCompanyRegistrationTypeRequest;
use Modules\Company\CompanyRegistrationType\Requests\GetCompanyRegistrationTypeListRequest;
use Modules\Company\CompanyRegistrationType\Requests\GetCompanyRegistrationTypeRequest;
use Modules\Company\CompanyRegistrationType\Requests\UpdateCompanyRegistrationTypeRequest;
use Modules\Company\CompanyRegistrationType\Services\CompanyRegistrationTypeCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyRegistrationTypeController extends Controller
{
    public function __construct(
        private CompanyRegistrationTypeCRUDService $companyRegistrationTypeService,
        private UpdateCompanyRegistrationTypeHandler $updateCompanyRegistrationTypeHandler,
        private DeleteCompanyRegistrationTypeHandler $deleteCompanyRegistrationTypeHandler,
    ) {
    }

    public function index(GetCompanyRegistrationTypeListRequest $request): JsonResponse
    {
        $list = $this->companyRegistrationTypeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );
        return Json::items(CompanyRegistrationTypePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetCompanyRegistrationTypeRequest $request): JsonResponse
    {
        $item = $this->companyRegistrationTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyRegistrationTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateCompanyRegistrationTypeRequest $request): JsonResponse
    {
        $createdItem = $this->companyRegistrationTypeService->create($request->createCreateCompanyRegistrationTypeDTO());

        $presenter = new CompanyRegistrationTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCompanyRegistrationTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyRegistrationTypeCommand();
        $this->updateCompanyRegistrationTypeHandler->handle($command);

        $item = $this->companyRegistrationTypeService->get($command->getId());

        $presenter = new CompanyRegistrationTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteCompanyRegistrationTypeRequest $request): JsonResponse
    {
        $this->deleteCompanyRegistrationTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
