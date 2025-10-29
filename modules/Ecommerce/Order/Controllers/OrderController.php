<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Order\Handlers\DeleteOrderHandler;
use Modules\Ecommerce\Order\Handlers\UpdateOrderHandler;
use Modules\Ecommerce\Order\Presenters\OrderPresenter;
use Modules\Ecommerce\Order\Presenters\OrderStatusHistoryPresenter;
use Modules\Ecommerce\Order\Requests\CreateOrderRequest;
use Modules\Ecommerce\Order\Requests\DeleteOrderRequest;
use Modules\Ecommerce\Order\Requests\GetOrderListRequest;
use Modules\Ecommerce\Order\Requests\GetOrderRequest;
use Modules\Ecommerce\Order\Requests\UpdateOrderRequest;
use Modules\Ecommerce\Order\Requests\UpdateOrderStatusRequest;
use Modules\Ecommerce\Order\Services\OrderCRUDService;
use Modules\Ecommerce\Order\Services\OrderStatusService;
use Modules\Ecommerce\Order\Exports\OrderExport;
use Modules\Ecommerce\Order\Requests\ExportOrderRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class OrderController extends Controller
{
    public function __construct(
        private OrderCRUDService $orderService,
        private OrderStatusService $orderStatusService,
        private UpdateOrderHandler $updateOrderHandler,
        private DeleteOrderHandler $deleteOrderHandler,
    ) {
    }

    public function index(GetOrderListRequest $request): JsonResponse
    {
        $list = $this->orderService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(OrderPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetOrderRequest $request): JsonResponse
    {
        $item = $this->orderService->get(Uuid::fromString($request->route('id')));

        $presenter = new OrderPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $createdItem = $this->orderService->create($request->createCreateOrderDTO());

        $presenter = new OrderPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateOrderRequest $request): JsonResponse
    {
        $command = $request->createUpdateOrderCommand();
        $this->updateOrderHandler->handle($command);

        $item = $this->orderService->get($command->getId());

        $presenter = new OrderPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteOrderRequest $request): JsonResponse
    {
        $this->deleteOrderHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export order to a file
     *
     * @param ExportOrderRequest $request
     */
    public function export(ExportOrderRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'order.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new OrderExport($this->orderService, $filters), $fileName);
    }

    public function updateStatus(UpdateOrderStatusRequest $request): JsonResponse
    {
        $dto = $request->createUpdateOrderStatusDTO();
        $updatedOrder = $this->orderStatusService->updateStatus($dto);

        $presenter = new OrderPresenter($updatedOrder);

        return Json::item($presenter->getData(), message: 'تم تحديث حالة الطلب بنجاح');
    }

    public function bulkUpdateStatus(UpdateOrderStatusRequest $request): JsonResponse
    {
        $orderIds = $request->validated()['order_ids'] ?? [];
        
        if (empty($orderIds)) {
            return response()->json([
                'success' => false,
                'message' => 'معرفات الطلبات مطلوبة'
            ], 400);
        }

        $result = $this->orderStatusService->bulkUpdateStatus(
            $orderIds,
            $request->getOrderStatus(),
            $request->getReason(),
            $request->getNotes()
        );

        return response()->json([
            'success' => true,
            'message' => "تم تحديث {$result['success_count']} طلب بنجاح",
            'data' => OrderPresenter::collection($result['updated_orders']),
            'errors' => $result['errors'],
            'summary' => [
                'success_count' => $result['success_count'],
                'error_count' => $result['error_count']
            ]
        ]);
    }

    public function getStatusHistory(GetOrderRequest $request): JsonResponse
    {
        $orderId = $request->route('id');
        $history = $this->orderStatusService->getStatusHistory($orderId);


        return Json::item(OrderStatusHistoryPresenter::collection($history),[
            'total_changes' => $history->count(),
            'order_id' => $orderId
        ]);

    }

    public function getAvailableStatuses(GetOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->get(Uuid::fromString($request->route('id')));
        $availableStatuses = $this->orderStatusService->getAvailableStatuses($order);

        return  Json::item([
                'current_status' => $order->order_status,
                'available_statuses' => $availableStatuses
        ]);
    }

    public function getStatistics(): JsonResponse
    {
        $statistics = OrderCRUDService::getOrdersStatistics();

        return Json::item($statistics, message: 'تم جلب إحصائيات الطلبات بنجاح');
    }
}
