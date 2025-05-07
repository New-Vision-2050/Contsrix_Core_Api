<?php

declare(strict_types=1);

namespace Modules\SubEntity\Controllers;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\SubEntity\Requests\GetSubEntityRequest;
use Modules\SubEntity\Presenters\SubEntityPresenter;
use Modules\SubEntity\Services\SubEntityCRUDService;
use Modules\SubEntity\Handlers\DeleteSubEntityHandler;
use Modules\SubEntity\Handlers\UpdateSubEntityHandler;
use Modules\SubEntity\Requests\CreateSubEntityRequest;
use Modules\SubEntity\Requests\DeleteSubEntityRequest;
use Modules\SubEntity\Requests\UpdateSubEntityRequest;
use Modules\SubEntity\Requests\GetSubEntityListRequest;
use Modules\SubEntity\Requests\GetSubEntityListByProgramNameRequest;

class SubEntityController extends Controller
{
    public function __construct(
        private SubEntityCRUDService $subEntityService,
        private UpdateSubEntityHandler $updateSubEntityHandler,
        private DeleteSubEntityHandler $deleteSubEntityHandler,
    ) {
    }

    public function index(GetSubEntityListRequest $request): JsonResponse
    {
        $list = $this->subEntityService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(SubEntityPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetSubEntityRequest $request): JsonResponse
    {
        $item = $this->subEntityService->get(Uuid::fromString($request->route('id')));

        $presenter = new SubEntityPresenter($item);

        return Json::item($presenter->getData());
    }

    public function showAttributes(GetSubEntityRequest $request): JsonResponse
    {
        $item = $this->subEntityService->get(Uuid::fromString($request->route('id')));

        $presenter = new SubEntityPresenter($item);

        return Json::item($presenter->getAttributes());
    }

    public function store(CreateSubEntityRequest $request): JsonResponse
    {
        $createdItem = $this->subEntityService->create($request->createCreateSubEntityDTO());

        $presenter = new SubEntityPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateSubEntityRequest $request): JsonResponse
    {
        $command = $request->createUpdateSubEntityCommand();
        $this->updateSubEntityHandler->handle($command);

        $item = $this->subEntityService->get($command->getId());

        $presenter = new SubEntityPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteSubEntityRequest $request): JsonResponse
    {
        $this->deleteSubEntityHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function getByProgram(GetSubEntityListByProgramNameRequest $request): JsonResponse
    {
        $result = $this->subEntityService->paginatedByProgramName(
            programName: $request->get('program_name'),
            page: (int) $request->get('page', 1),
            perPage: (int) $request->get('per_page', 10),
        );

        return Json::items(
            SubEntityPresenter::collection($result['data']),
            paginationSettings: $result['pagination']
        );
    }
}
