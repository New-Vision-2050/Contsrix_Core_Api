<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Services;

use Modules\Ecommerce\Order\DTO\UpdateOrderStatusDTO;
use Modules\Ecommerce\Order\Models\Order;
use Modules\Ecommerce\Order\Repositories\OrderRepository;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderStatusService
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
    }

    public function updateStatus(UpdateOrderStatusDTO $dto): Order
    {
        $order = $this->orderRepository->getOrder($dto->getOrderId());
        
        // Store previous statuses for history
        $previousOrderStatus = $order->order_status;
        $previousPaymentStatus = $order->payment_status;
        
        // Prepare update data
        $updateData = ['order_status' => $dto->getOrderStatus()];
        
        if ($dto->getPaymentStatus()) {
            $updateData['payment_status'] = $dto->getPaymentStatus();
        }
        
        // Update the order
        $order->update($updateData);
        $order->refresh();
        
        // Create status history for order status change
        if ($previousOrderStatus !== $dto->getOrderStatus()) {
            $this->createStatusHistory(
                $order,
                $dto->getOrderStatus(),
                $previousOrderStatus,
                $dto->getReason(),
                $dto->getNotes(),
                'order'
            );
        }
        
        // Create status history for payment status change if applicable
        if ($dto->getPaymentStatus() && $previousPaymentStatus !== $dto->getPaymentStatus()) {
            $this->createStatusHistory(
                $order,
                $dto->getPaymentStatus(),
                $previousPaymentStatus,
                $dto->getReason() ?? 'تحديث حالة الدفع',
                $dto->getNotes(),
                'payment'
            );
        }
        
        return $order->load(['orderDetails', 'statusHistories', 'warehouse', 'customer']);
    }

    public function bulkUpdateStatus(array $orderIds, string $orderStatus, ?string $reason = null, ?string $notes = null): array
    {
        $updatedOrders = [];
        $errors = [];
        
        foreach ($orderIds as $orderId) {
            try {
                $dto = new UpdateOrderStatusDTO(
                    orderId: Uuid::fromString($orderId),
                    orderStatus: $orderStatus,
                    paymentStatus: null,
                    reason: $reason,
                    notes: $notes
                );
                
                $updatedOrders[] = $this->updateStatus($dto);
            } catch (\Exception $e) {
                $errors[] = [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ];
                Log::error("Failed to update order {$orderId}: " . $e->getMessage());
            }
        }
        
        return [
            'updated_orders' => $updatedOrders,
            'errors' => $errors,
            'success_count' => count($updatedOrders),
            'error_count' => count($errors)
        ];
    }

    public function getStatusHistory(string $orderId): \Illuminate\Database\Eloquent\Collection
    {
        $order = $this->orderRepository->getOrder(Uuid::fromString($orderId));
        
        return $order->statusHistories()
            ->with(['changedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function canUpdateStatus(Order $order, string $newStatus): bool
    {
        $currentStatus = $order->order_status;
        
        // Define allowed status transitions
        $allowedTransitions = [
            'pending' => ['confirmed', 'canceled'],
            'confirmed' => ['processing', 'canceled'],
            'processing' => ['shipped', 'canceled'],
            'shipped' => ['out_for_delivery', 'returned'],
            'out_for_delivery' => ['delivered', 'returned'],
            'delivered' => ['returned'],
            'returned' => [],
            'failed' => ['pending'],
            'canceled' => ['pending']
        ];
        
        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }

    public function validateStatusTransition(Order $order, string $newStatus): void
    {
        if (!$this->canUpdateStatus($order, $newStatus)) {
            throw new \InvalidArgumentException(
                "لا يمكن تغيير حالة الطلب من '{$order->order_status}' إلى '{$newStatus}'"
            );
        }
    }

    public function getAvailableStatuses(Order $order): array
    {
        $currentStatus = $order->order_status;
        
        $statusTransitions = [
            'pending' => [
                ['status' => 'confirmed', 'label' => 'مؤكد', 'color' => 'success'],
                ['status' => 'canceled', 'label' => 'ملغي', 'color' => 'danger']
            ],
            'confirmed' => [
                ['status' => 'processing', 'label' => 'قيد المعالجة', 'color' => 'info'],
                ['status' => 'canceled', 'label' => 'ملغي', 'color' => 'danger']
            ],
            'processing' => [
                ['status' => 'shipped', 'label' => 'تم الشحن', 'color' => 'primary'],
                ['status' => 'canceled', 'label' => 'ملغي', 'color' => 'danger']
            ],
            'shipped' => [
                ['status' => 'out_for_delivery', 'label' => 'خرج للتوصيل', 'color' => 'warning'],
                ['status' => 'returned', 'label' => 'مرتجع', 'color' => 'secondary']
            ],
            'out_for_delivery' => [
                ['status' => 'delivered', 'label' => 'تم التوصيل', 'color' => 'success'],
                ['status' => 'returned', 'label' => 'مرتجع', 'color' => 'secondary']
            ],
            'delivered' => [
                ['status' => 'returned', 'label' => 'مرتجع', 'color' => 'secondary']
            ],
            'returned' => [],
            'failed' => [
                ['status' => 'pending', 'label' => 'في الانتظار', 'color' => 'warning']
            ],
            'canceled' => [
                ['status' => 'pending', 'label' => 'في الانتظار', 'color' => 'warning']
            ]
        ];
        
        return $statusTransitions[$currentStatus] ?? [];
    }

    private function createStatusHistory(
        Order $order,
        string $status,
        ?string $previousStatus,
        ?string $reason,
        ?string $notes,
        string $type = 'order'
    ): void {
        $order->statusHistories()->create([
            'company_id' => $order->company_id,
            'user_type' => Auth::check() ? 'admin' : 'system',
            'status' => $status,
            'previous_status' => $previousStatus,
            'changed_by' => Auth::id(),
            'reason' => $reason ?? ($type === 'payment' ? 'تحديث حالة الدفع' : 'تحديث حالة الطلب'),
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }
}
