<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\BusinessType\Handlers\DeleteBusinessTypeHandler;
use Modules\Company\BusinessType\Handlers\UpdateBusinessTypeHandler;
use Modules\Company\BusinessType\Presenters\BusinessTypePresenter;
use Modules\Company\BusinessType\Requests\CreateBusinessTypeRequest;
use Modules\Company\BusinessType\Requests\DeleteBusinessTypeRequest;
use Modules\Company\BusinessType\Requests\GetBusinessTypeListRequest;
use Modules\Company\BusinessType\Requests\GetBusinessTypeRequest;
use Modules\Company\BusinessType\Requests\UpdateBusinessTypeRequest;
use Modules\Company\BusinessType\Services\BusinessTypeCRUDService;
use Ramsey\Uuid\Uuid;

class BusinessTypeController extends Controller
{
    public function __construct(
        private BusinessTypeCRUDService $businessTypeService,
        private UpdateBusinessTypeHandler $updateBusinessTypeHandler,
        private DeleteBusinessTypeHandler $deleteBusinessTypeHandler,
    ) {
    }

    public function index(GetBusinessTypeListRequest $request): JsonResponse
    {
        $list = $this->businessTypeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(BusinessTypePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetBusinessTypeRequest $request): JsonResponse
    {
        $item = $this->businessTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new BusinessTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateBusinessTypeRequest $request): JsonResponse
    {
        $createdItem = $this->businessTypeService->create($request->createCreateBusinessTypeDTO());

        $presenter = new BusinessTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateBusinessTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateBusinessTypeCommand();
        $this->updateBusinessTypeHandler->handle($command);

        $item = $this->businessTypeService->get($command->getId());

        $presenter = new BusinessTypePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteBusinessTypeRequest $request): JsonResponse
    {
        $this->deleteBusinessTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
