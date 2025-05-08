<?php

declare(strict_types=1);

namespace Modules\Shared\University\Controllers;

use App\Http\Controllers\Controller;
use Modules\Shared\University\Handlers\DeleteUniversityHandler;
use Modules\Shared\University\Handlers\UpdateUniversityHandler;
use Modules\Shared\University\Presenters\UniversityPresenter;
use Modules\Shared\University\Requests\CreateUniversityRequest;
use Modules\Shared\University\Requests\DeleteUniversityRequest;
use Modules\Shared\University\Requests\GetUniversityListRequest;
use Modules\Shared\University\Requests\GetUniversityRequest;
use Modules\Shared\University\Requests\UpdateUniversityRequest;
use Modules\Shared\University\Services\UniversityCRUDService;
use Ramsey\Uuid\Uuid;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
class UniversityController extends Controller
{
    public function __construct(
        private UniversityCRUDService $universityService,
        private UpdateUniversityHandler $updateUniversityHandler,
        private DeleteUniversityHandler $deleteUniversityHandler,
    ) {
    }

    public function index(GetUniversityListRequest $request): JsonResponse
    {
        $list = $this->universityService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UniversityPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUniversityRequest $request): JsonResponse
    {
        $item = $this->universityService->get(Uuid::fromString($request->route('id')));

        $presenter = new UniversityPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUniversityRequest $request): JsonResponse
    {
        $createdItem = $this->universityService->create($request->createCreateUniversityDTO());

        $presenter = new UniversityPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUniversityRequest $request): JsonResponse
    {
        $command = $request->createUpdateUniversityCommand();
        $this->updateUniversityHandler->handle($command);

        $item = $this->universityService->get($command->getId());

        $presenter = new UniversityPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUniversityRequest $request): JsonResponse
    {
        $this->deleteUniversityHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
