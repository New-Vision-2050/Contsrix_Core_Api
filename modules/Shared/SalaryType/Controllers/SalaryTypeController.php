<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\SalaryType\Handlers\DeleteSalaryTypeHandler;
use Modules\Shared\SalaryType\Handlers\UpdateSalaryTypeHandler;
use Modules\Shared\SalaryType\Presenters\SalaryTypePresenter;
use Modules\Shared\SalaryType\Requests\CreateSalaryTypeRequest;
use Modules\Shared\SalaryType\Requests\DeleteSalaryTypeRequest;
use Modules\Shared\SalaryType\Requests\GetSalaryTypeListRequest;
use Modules\Shared\SalaryType\Requests\GetSalaryTypeRequest;
use Modules\Shared\SalaryType\Requests\UpdateSalaryTypeRequest;
use Modules\Shared\SalaryType\Services\SalaryTypeCRUDService;
use Ramsey\Uuid\Uuid;

class SalaryTypeController extends Controller
{
    public function __construct(
        private SalaryTypeCRUDService $salaryTypeService,
        private UpdateSalaryTypeHandler $updateSalaryTypeHandler,
        private DeleteSalaryTypeHandler $deleteSalaryTypeHandler,
    ) {
    }

    public function index(GetSalaryTypeListRequest $request): JsonResponse
    {
        $list = $this->salaryTypeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(SalaryTypePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetSalaryTypeRequest $request): JsonResponse
    {
        $item = $this->salaryTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new SalaryTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateSalaryTypeRequest $request): JsonResponse
    {
        $createdItem = $this->salaryTypeService->create($request->createCreateSalaryTypeDTO());

        $presenter = new SalaryTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateSalaryTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateSalaryTypeCommand();
        $this->updateSalaryTypeHandler->handle($command);

        $item = $this->salaryTypeService->get($command->getId());

        $presenter = new SalaryTypePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteSalaryTypeRequest $request): JsonResponse
    {
        $this->deleteSalaryTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
