<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\SubscriptionSystem\ProgramSystem\Handlers\DeleteProgramSystemHandler;
use Modules\SubscriptionSystem\ProgramSystem\Handlers\UpdateProgramSystemHandler;
use Modules\SubscriptionSystem\ProgramSystem\Presenters\ProgramSystemIndexPresenter;
use Modules\SubscriptionSystem\ProgramSystem\Presenters\ProgramSystemPresenter;
use Modules\SubscriptionSystem\ProgramSystem\Requests\CreateProgramSystemRequest;
use Modules\SubscriptionSystem\ProgramSystem\Requests\DeleteProgramSystemRequest;
use Modules\SubscriptionSystem\ProgramSystem\Requests\GetProgramSystemListRequest;
use Modules\SubscriptionSystem\ProgramSystem\Requests\GetProgramSystemRequest;
use Modules\SubscriptionSystem\ProgramSystem\Requests\UpdateProgramSystemRequest;
use Modules\SubscriptionSystem\ProgramSystem\Services\ProgramSystemCRUDService;
use Modules\SubscriptionSystem\ProgramSystem\Services\ProgramSystemWidgetService;
use Ramsey\Uuid\Uuid;

class ProgramSystemController extends Controller
{
    public function __construct(
        private ProgramSystemCRUDService $programSystemService,
        private UpdateProgramSystemHandler $updateProgramSystemHandler,
        private DeleteProgramSystemHandler $deleteProgramSystemHandler,
        private ProgramSystemWidgetService $programSystemWidgetService
    ) {
    }

    public function index(GetProgramSystemListRequest $request): JsonResponse
    {
        $list = $this->programSystemService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ProgramSystemIndexPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function widget(GetProgramSystemListRequest $request): JsonResponse
    {
        $list = $this->programSystemWidgetService->widget();
        return Json::item(['program' => $list]);
    }


    public function list(GetProgramSystemListRequest $request): JsonResponse
    {
        $list = $this->programSystemService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ProgramSystemPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetProgramSystemRequest $request): JsonResponse
    {
        $item = $this->programSystemService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProgramSystemPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProgramSystemRequest $request): JsonResponse
    {
        $createdItem = $this->programSystemService->create($request->createCreateProgramSystemDTO());

        $presenter = new ProgramSystemPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProgramSystemRequest $request): JsonResponse
    {
        $command = $request->createUpdateProgramSystemCommand();
        $this->updateProgramSystemHandler->handle($command);

        $item = $this->programSystemService->get($command->getId());

        $presenter = new ProgramSystemPresenter($item);

        return Json::item( $presenter->getData());
    }
    public function toggleIsActive(GetProgramSystemRequest $request): JsonResponse
    {
        $item = $this->programSystemService->toggleIsActive(Uuid::fromString($request->route('id')));

        $presenter = new ProgramSystemIndexPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteProgramSystemRequest $request): JsonResponse
    {
        $this->deleteProgramSystemHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
