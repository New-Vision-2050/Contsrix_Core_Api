<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\NatureWork\Handlers\DeleteNatureWorkHandler;
use Modules\Shared\NatureWork\Handlers\UpdateNatureWorkHandler;
use Modules\Shared\NatureWork\Presenters\NatureWorkPresenter;
use Modules\Shared\NatureWork\Requests\CreateNatureWorkRequest;
use Modules\Shared\NatureWork\Requests\DeleteNatureWorkRequest;
use Modules\Shared\NatureWork\Requests\GetNatureWorkListRequest;
use Modules\Shared\NatureWork\Requests\GetNatureWorkRequest;
use Modules\Shared\NatureWork\Requests\UpdateNatureWorkRequest;
use Modules\Shared\NatureWork\Services\NatureWorkCRUDService;
use Ramsey\Uuid\Uuid;

class NatureWorkController extends Controller
{
    public function __construct(
        private NatureWorkCRUDService $natureWorkService,
        private UpdateNatureWorkHandler $updateNatureWorkHandler,
        private DeleteNatureWorkHandler $deleteNatureWorkHandler,
    ) {
    }

    public function index(GetNatureWorkListRequest $request): JsonResponse
    {
        $list = $this->natureWorkService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(NatureWorkPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetNatureWorkRequest $request): JsonResponse
    {
        $item = $this->natureWorkService->get(Uuid::fromString($request->route('id')));

        $presenter = new NatureWorkPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateNatureWorkRequest $request): JsonResponse
    {
        $createdItem = $this->natureWorkService->create($request->createCreateNatureWorkDTO());

        $presenter = new NatureWorkPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateNatureWorkRequest $request): JsonResponse
    {
        $command = $request->createUpdateNatureWorkCommand();
        $this->updateNatureWorkHandler->handle($command);

        $item = $this->natureWorkService->get($command->getId());

        $presenter = new NatureWorkPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteNatureWorkRequest $request): JsonResponse
    {
        $this->deleteNatureWorkHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
