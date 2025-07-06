<?php

declare(strict_types=1);

namespace Modules\Program\Controllers;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\Program\Requests\GetProgramRequest;
use Modules\Program\Presenters\ProgramPresenter;
use Modules\Program\Services\ProgramCRUDService;
use Modules\Program\Handlers\DeleteProgramHandler;
use Modules\Program\Handlers\UpdateProgramHandler;
use Modules\Program\Requests\CreateProgramRequest;
use Modules\Program\Requests\DeleteProgramRequest;
use Modules\Program\Requests\UpdateProgramRequest;
use Modules\Program\Requests\GetProgramListRequest;
use Modules\Program\Presenters\ProgramSelectListPresenter;

class ProgramController extends Controller
{
    public function __construct(
        private ProgramCRUDService $programService,
        private UpdateProgramHandler $updateProgramHandler,
        private DeleteProgramHandler $deleteProgramHandler,
    ) {
    }

    public function index(GetProgramListRequest $request): JsonResponse
    {
        $list = $this->programService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ProgramPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetProgramRequest $request): JsonResponse
    {
        $item = $this->programService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProgramPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProgramRequest $request): JsonResponse
    {
        $createdItem = $this->programService->create($request->createCreateProgramDTO());

        $presenter = new ProgramPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProgramRequest $request): JsonResponse
    {
        $command = $request->createUpdateProgramCommand();
        $this->updateProgramHandler->handle($command);

        $item = $this->programService->get($command->getId());

        $presenter = new ProgramPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteProgramRequest $request): JsonResponse
    {
        $this->deleteProgramHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function listWithSubEntities(): JsonResponse
    {
        $list = $this->programService->list(
            (int) request()->get('page', 1),
            (int) request()->get('per_page', 10)
        );

        return Json::items(ProgramPresenter::collectionWithSubEntities($list['data']), paginationSettings: $list['pagination']);
    }

    public function selectListWithSubEntities(): JsonResponse
    {
        $programs = $this->programService->selectList();

        return Json::items(ProgramSelectListPresenter::collection($programs));
    }

}
