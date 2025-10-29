<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Presenters;

use Modules\Ecommerce\Order\Models\OrderStatusHistory;
use BasePackage\Shared\Presenters\AbstractPresenter;

class OrderStatusHistoryPresenter extends AbstractPresenter
{
    private OrderStatusHistory $orderStatusHistory;

    public function __construct(OrderStatusHistory $orderStatusHistory)
    {
        $this->orderStatusHistory = $orderStatusHistory;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->orderStatusHistory->id,
            'order_id' => $this->orderStatusHistory->order_id,
            'status' => $this->orderStatusHistory->status,
            'previous_status' => $this->orderStatusHistory->previous_status,
            'user_type' => $this->orderStatusHistory->user_type,
            'changed_by_name' => $this->getChangedByName(),
            'reason' => $this->orderStatusHistory->reason,
            'notes' => $this->orderStatusHistory->notes,
            'changed_at' => $this->orderStatusHistory->changed_at?->format('Y-m-d H:i:s'),
            'changed_at_human' => $this->orderStatusHistory->changed_at?->diffForHumans(),
            'created_at' => $this->orderStatusHistory->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->orderStatusHistory->created_at->diffForHumans(),
        ];
    }



    private function getChangedByName(): ?string
    {
        if (!$this->orderStatusHistory->changed_by) {
            return null;
        }

        // Try to get the user name from the relationship if it exists
        if ($this->orderStatusHistory->relationLoaded('changedBy') && $this->orderStatusHistory->changedBy) {
            return $this->orderStatusHistory->changedBy->name ?? 'مستخدم غير معروف';
        }

        return 'مستخدم';
    }
}
