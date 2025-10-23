<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\FeatureDeal\Handlers\DeleteFeatureDealHandler;
use Modules\Ecommerce\FeatureDeal\Handlers\UpdateFeatureDealHandler;
use Modules\Ecommerce\FeatureDeal\Presenters\FeatureDealPresenter;
use Modules\Ecommerce\FeatureDeal\Requests\CreateFeatureDealRequest;
use Modules\Ecommerce\FeatureDeal\Requests\DeleteFeatureDealRequest;
use Modules\Ecommerce\FeatureDeal\Requests\GetFeatureDealListRequest;
use Modules\Ecommerce\FeatureDeal\Requests\GetFeatureDealRequest;
use Modules\Ecommerce\FeatureDeal\Requests\UpdateFeatureDealRequest;
use Modules\Ecommerce\FeatureDeal\Services\FeatureDealCRUDService;
use Modules\Ecommerce\FeatureDeal\Exports\FeatureDealExport;
use Modules\Ecommerce\FeatureDeal\Requests\ExportFeatureDealRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class FeatureDealController extends Controller
{
    public function __construct(
        private FeatureDealCRUDService $featureDealService,
        private UpdateFeatureDealHandler $updateFeatureDealHandler,
        private DeleteFeatureDealHandler $deleteFeatureDealHandler,
    ) {
    }

    public function index(GetFeatureDealListRequest $request): JsonResponse
    {
        $list = $this->featureDealService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FeatureDealPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFeatureDealRequest $request): JsonResponse
    {
        $item = $this->featureDealService->get(Uuid::fromString($request->route('id')));

        $presenter = new FeatureDealPresenter($item);

        return Json::item($presenter->getData(false)); // false = details view, not listing
    }

    public function store(CreateFeatureDealRequest $request): JsonResponse
    {
        $createdItem = $this->featureDealService->create($request->createCreateFeatureDealDTO());

        $presenter = new FeatureDealPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateFeatureDealRequest $request): JsonResponse
    {
        $command = $request->createUpdateFeatureDealCommand();
        $this->updateFeatureDealHandler->handle($command);

        $item = $this->featureDealService->get($command->getId());

        $presenter = new FeatureDealPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteFeatureDealRequest $request): JsonResponse
    {
        $this->deleteFeatureDealHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Toggle feature deal active status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $result = $this->featureDealService->toggleStatus(Uuid::fromString($id));
            
            return Json::item([
                'message' => $result['message'],
                'is_active' => $result['is_active'],
                'status_text' => $result['status_text']
            ]);
        } catch (\Exception $e) {
            return Json::error('فشل في تغيير حالة العرض المميز: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get feature deal statistics cards for dashboard
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->featureDealService->getFeatureDealStatistics();

        return Json::item($stats);
    }

    /**
     * Export featuredeal to a file
     *
     * @param ExportFeatureDealRequest $request
     */
    public function export(ExportFeatureDealRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'feature_deal.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new FeatureDealExport($this->featureDealService, $filters), $fileName);
    }
}
