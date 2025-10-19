<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoBrand\Handlers\Dashboard\DeleteEcoBrandDashboardHandler;
use Modules\Ecommerce\EcoBrand\Handlers\Dashboard\UpdateEcoBrandDashboardHandler;
use Modules\Ecommerce\EcoBrand\Presenters\Dashboard\EcoBrandDashboardPresenter;
use Modules\Ecommerce\EcoBrand\Requests\Dashboard\CreateEcoBrandDashboardRequest;
use Modules\Ecommerce\EcoBrand\Requests\Dashboard\DeleteEcoBrandDashboardRequest;
use Modules\Ecommerce\EcoBrand\Requests\Dashboard\GetEcoBrandDashboardRequest;
use Modules\Ecommerce\EcoBrand\Requests\Dashboard\GetEcoBrandListDashboardRequest;
use Modules\Ecommerce\EcoBrand\Requests\Dashboard\UpdateEcoBrandDashboardRequest;
use Modules\Ecommerce\EcoBrand\Services\Dashboard\EcoBrandCRUDDashboardService;
use Ramsey\Uuid\Uuid;

class EcoBrandDashboardController extends Controller
{
    public function __construct(
        private EcoBrandCRUDDashboardService $ecoBrandService,
        private UpdateEcoBrandDashboardHandler $updateEcoBrandHandler,
        private DeleteEcoBrandDashboardHandler $deleteEcoBrandHandler,
    ) {
    }

    public function index(GetEcoBrandListDashboardRequest $request): JsonResponse
    {
        $list = $this->ecoBrandService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoBrandDashboardPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoBrandDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoBrandService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoBrandDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoBrandDashboardRequest $request): JsonResponse
    {
        $file = $request->file('brand_image');
        $createdItem = $this->ecoBrandService->create($request->createCreateEcoBrandDTO(), $file);

        $presenter = new EcoBrandDashboardPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoBrandDashboardRequest $request): JsonResponse
    {
        $file = $request->file('brand_image');
        $command = $request->createUpdateEcoBrandCommand();
        $this->updateEcoBrandHandler->handle($command, $file);

        $item = $this->ecoBrandService->get($command->getId());

        $presenter = new EcoBrandDashboardPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoBrandDashboardRequest $request): JsonResponse
    {
        $this->deleteEcoBrandHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
