<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\EcoDiscount\Handlers\DeleteEcoDiscountHandler;
use Modules\Ecommerce\EcoDiscount\Handlers\UpdateEcoDiscountHandler;
use Modules\Ecommerce\EcoDiscount\Handlers\UpdateEcoDiscountProductHandler;
use Modules\Ecommerce\EcoDiscount\Presenters\EcoDiscountPresenter;
use Modules\Ecommerce\EcoDiscount\Requests\CreateEcoDiscountRequest;
use Modules\Ecommerce\EcoDiscount\Requests\DeleteEcoDiscountRequest;
use Modules\Ecommerce\EcoDiscount\Requests\GetEcoDiscountListRequest;
use Modules\Ecommerce\EcoDiscount\Requests\GetEcoDiscountRequest;
use Modules\Ecommerce\EcoDiscount\Requests\UpdateEcoDiscountRequest;
use Modules\Ecommerce\EcoDiscount\Services\EcoDiscountCRUDService;
use Modules\Ecommerce\EcoDiscount\Exports\EcoDiscountExport;
use Modules\Ecommerce\EcoDiscount\Requests\ExportEcoDiscountRequest;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\EcoDiscount\Presenters\EcoDiscountProductPresenter;
use Modules\Ecommerce\EcoDiscount\Requests\CreateEcoDiscountProductRequest;
use Modules\Ecommerce\EcoProduct\Presenters\EcoProductPresenter;
use Ramsey\Uuid\Uuid;

class EcoDiscountController extends Controller
{
    public function __construct(
        private EcoDiscountCRUDService $ecoDiscountService,
        private UpdateEcoDiscountHandler $updateEcoDiscountHandler,
        private DeleteEcoDiscountHandler $deleteEcoDiscountHandler,
        private UpdateEcoDiscountProductHandler $updateEcoDiscountProductHandler,
    ) {
    }

    public function index(GetEcoDiscountListRequest $request): JsonResponse
    {
        $list = $this->ecoDiscountService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoDiscountPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoDiscountRequest $request): JsonResponse
    {
        $item = $this->ecoDiscountService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoDiscountPresenter($item);

        return Json::item($presenter->getData());
    }
    public function storeDiscountProduct(CreateEcoDiscountProductRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoDiscountProductCommand();
        $this->updateEcoDiscountProductHandler->handle($command);

        $item = $this->ecoDiscountService->getProduct($command->getId());
        $presenter = new EcoDiscountProductPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoDiscountRequest $request): JsonResponse
    {
        $createdItem = $this->ecoDiscountService->create($request->createCreateEcoDiscountDTO());

        $presenter = new EcoDiscountPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoDiscountRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoDiscountCommand();
        $this->updateEcoDiscountHandler->handle($command);

        $item = $this->ecoDiscountService->get($command->getId());

        $presenter = new EcoDiscountPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoDiscountRequest $request): JsonResponse
    {
        $this->deleteEcoDiscountHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Get discount statistics
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->ecoDiscountService->getStatistics();

        return Json::item($statistics);
    }

    /**
     * Apply discount code to order
     */
    public function applyDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:eco_products,id',
        ]);

        $result = $this->ecoDiscountService->applyDiscountCode(
            $validated['code'],
            $validated['order_amount'],
            $validated['product_ids'] ?? []
        );

        if ($result['success']) {
            return Json::item($result);
        } else {
            return Json::error($result['message'], 422);
        }
    }

    /**
     * Export ecodiscount to a file
     *
     * @param ExportEcoDiscountRequest $request
     */
    public function export(ExportEcoDiscountRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_discount.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new EcoDiscountExport($this->ecoDiscountService, $filters), $fileName);
    }
}
