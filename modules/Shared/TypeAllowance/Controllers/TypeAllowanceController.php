<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\TypeAllowance\Handlers\DeleteTypeAllowanceHandler;
use Modules\Shared\TypeAllowance\Handlers\UpdateTypeAllowanceHandler;
use Modules\Shared\TypeAllowance\Presenters\TypeAllowancePresenter;
use Modules\Shared\TypeAllowance\Requests\CreateTypeAllowanceRequest;
use Modules\Shared\TypeAllowance\Requests\DeleteTypeAllowanceRequest;
use Modules\Shared\TypeAllowance\Requests\GetTypeAllowanceListRequest;
use Modules\Shared\TypeAllowance\Requests\GetTypeAllowanceRequest;
use Modules\Shared\TypeAllowance\Requests\UpdateTypeAllowanceRequest;
use Modules\Shared\TypeAllowance\Services\TypeAllowanceCRUDService;
use Ramsey\Uuid\Uuid;

class TypeAllowanceController extends Controller
{
    public function __construct(
        private TypeAllowanceCRUDService $typeAllowanceService,
        private UpdateTypeAllowanceHandler $updateTypeAllowanceHandler,
        private DeleteTypeAllowanceHandler $deleteTypeAllowanceHandler,
    ) {
    }

    public function index(GetTypeAllowanceListRequest $request): JsonResponse
    {
        $list = $this->typeAllowanceService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TypeAllowancePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTypeAllowanceRequest $request): JsonResponse
    {
        $item = $this->typeAllowanceService->get(Uuid::fromString($request->route('id')));

        $presenter = new TypeAllowancePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTypeAllowanceRequest $request): JsonResponse
    {
        $createdItem = $this->typeAllowanceService->create($request->createCreateTypeAllowanceDTO());

        $presenter = new TypeAllowancePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTypeAllowanceRequest $request): JsonResponse
    {
        $command = $request->createUpdateTypeAllowanceCommand();
        $this->updateTypeAllowanceHandler->handle($command);

        $item = $this->typeAllowanceService->get($command->getId());

        $presenter = new TypeAllowancePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTypeAllowanceRequest $request): JsonResponse
    {
        $this->deleteTypeAllowanceHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
