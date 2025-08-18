<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Leave\PublicHoliday\Handlers\DeletePublicHolidayHandler;
use Modules\Leave\PublicHoliday\Handlers\UpdatePublicHolidayHandler;
use Modules\Leave\PublicHoliday\Presenters\PublicHolidayPresenter;
use Modules\Leave\PublicHoliday\Requests\CreatePublicHolidayRequest;
use Modules\Leave\PublicHoliday\Requests\DeletePublicHolidayRequest;
use Modules\Leave\PublicHoliday\Requests\GetPublicHolidayListRequest;
use Modules\Leave\PublicHoliday\Requests\GetPublicHolidayRequest;
use Modules\Leave\PublicHoliday\Requests\UpdatePublicHolidayRequest;
use Modules\Leave\PublicHoliday\Services\PublicHolidayCRUDService;
use Ramsey\Uuid\Uuid;

class PublicHolidayController extends Controller
{
    public function __construct(
        private PublicHolidayCRUDService $publicHolidayService,
        private UpdatePublicHolidayHandler $updatePublicHolidayHandler,
        private DeletePublicHolidayHandler $deletePublicHolidayHandler,
    ) {
    }

    public function index(GetPublicHolidayListRequest $request): JsonResponse
    {
        $list = $this->publicHolidayService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(PublicHolidayPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetPublicHolidayRequest $request): JsonResponse
    {
        $item = $this->publicHolidayService->get(Uuid::fromString($request->route('id')));

        $presenter = new PublicHolidayPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreatePublicHolidayRequest $request): JsonResponse
    {
        $createdItem = $this->publicHolidayService->create($request->createCreatePublicHolidayDTO());

        $presenter = new PublicHolidayPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePublicHolidayRequest $request): JsonResponse
    {
        $command = $request->createUpdatePublicHolidayCommand();
        $this->updatePublicHolidayHandler->handle($command);

        $item = $this->publicHolidayService->get($command->getId());

        $presenter = new PublicHolidayPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeletePublicHolidayRequest $request): JsonResponse
    {
        $this->deletePublicHolidayHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
