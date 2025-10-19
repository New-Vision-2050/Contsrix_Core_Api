<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoCategory\Handlers\Dashboard\DeleteEcoCategoryDashboardHandler;
use Modules\Ecommerce\EcoCategory\Handlers\Dashboard\UpdateEcoCategoryDashboardHandler;
use Modules\Ecommerce\EcoCategory\Presenters\Dashboard\EcoCategoryDashboardPresenter;
use Modules\Ecommerce\EcoCategory\Requests\Dashboard\CreateEcoCategoryDashboardRequest;
use Modules\Ecommerce\EcoCategory\Requests\Dashboard\DeleteEcoCategoryDashboardRequest;
use Modules\Ecommerce\EcoCategory\Requests\Dashboard\GetEcoCategoryDashboardRequest;
use Modules\Ecommerce\EcoCategory\Requests\Dashboard\GetEcoCategoryListDashboardRequest;
use Modules\Ecommerce\EcoCategory\Requests\Dashboard\UpdateEcoCategoryDashboardRequest;
use Modules\Ecommerce\EcoCategory\Services\Dashboard\EcoCategoryCRUDDashboardService;
use Ramsey\Uuid\Uuid;

class EcoCategoryDashboardController extends Controller
{
    public function __construct(
        private EcoCategoryCRUDDashboardService $ecoCategoryService,
        private UpdateEcoCategoryDashboardHandler $updateEcoCategoryHandler,
        private DeleteEcoCategoryDashboardHandler $deleteEcoCategoryHandler,
    ) {
    }

    public function index(GetEcoCategoryListDashboardRequest $request): JsonResponse
    {
        $list = $this->ecoCategoryService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            ['children', 'parent']
        );

        return Json::items(EcoCategoryDashboardPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoCategoryDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoCategoryService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoCategoryDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoCategoryDashboardRequest $request): JsonResponse
    {
        $file = $request->file('category_image');
        $createdItem = $this->ecoCategoryService->create($request->createCreateEcoCategoryDTO(), $file);

        $presenter = new EcoCategoryDashboardPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoCategoryDashboardRequest $request): JsonResponse
    {
        $file = $request->file('category_image');
        $command = $request->createUpdateEcoCategoryCommand();
        $this->updateEcoCategoryHandler->handle($command, $file);

        $item = $this->ecoCategoryService->get($command->getId());

        $presenter = new EcoCategoryDashboardPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoCategoryDashboardRequest $request): JsonResponse
    {
        $this->deleteEcoCategoryHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Get category statistics cards for dashboard
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->ecoCategoryService->getCategoryStatistics();

        return Json::item($stats);
    }
}
