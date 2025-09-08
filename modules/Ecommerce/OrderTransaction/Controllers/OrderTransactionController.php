<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\OrderTransaction\Handlers\DeleteOrderTransactionHandler;
use Modules\Ecommerce\OrderTransaction\Handlers\UpdateOrderTransactionHandler;
use Modules\Ecommerce\OrderTransaction\Presenters\OrderTransactionPresenter;
use Modules\Ecommerce\OrderTransaction\Requests\CreateOrderTransactionRequest;
use Modules\Ecommerce\OrderTransaction\Requests\DeleteOrderTransactionRequest;
use Modules\Ecommerce\OrderTransaction\Requests\GetOrderTransactionListRequest;
use Modules\Ecommerce\OrderTransaction\Requests\GetOrderTransactionRequest;
use Modules\Ecommerce\OrderTransaction\Requests\UpdateOrderTransactionRequest;
use Modules\Ecommerce\OrderTransaction\Services\OrderTransactionCRUDService;
use Modules\Ecommerce\OrderTransaction\Exports\OrderTransactionExport;
use Modules\Ecommerce\OrderTransaction\Requests\ExportOrderTransactionRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class OrderTransactionController extends Controller
{
    public function __construct(
        private OrderTransactionCRUDService $orderTransactionService,
        private UpdateOrderTransactionHandler $updateOrderTransactionHandler,
        private DeleteOrderTransactionHandler $deleteOrderTransactionHandler,
    ) {
    }

    public function index(GetOrderTransactionListRequest $request): JsonResponse
    {
        $list = $this->orderTransactionService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(OrderTransactionPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetOrderTransactionRequest $request): JsonResponse
    {
        $item = $this->orderTransactionService->get(Uuid::fromString($request->route('id')));

        $presenter = new OrderTransactionPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateOrderTransactionRequest $request): JsonResponse
    {
        $createdItem = $this->orderTransactionService->create($request->createCreateOrderTransactionDTO());

        $presenter = new OrderTransactionPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateOrderTransactionRequest $request): JsonResponse
    {
        $command = $request->createUpdateOrderTransactionCommand();
        $this->updateOrderTransactionHandler->handle($command);

        $item = $this->orderTransactionService->get($command->getId());

        $presenter = new OrderTransactionPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteOrderTransactionRequest $request): JsonResponse
    {
        $this->deleteOrderTransactionHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ordertransaction to a file
     *
     * @param ExportOrderTransactionRequest $request
     */
    public function export(ExportOrderTransactionRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'order_transaction.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new OrderTransactionExport($this->orderTransactionService, $filters), $fileName);
    }
}
