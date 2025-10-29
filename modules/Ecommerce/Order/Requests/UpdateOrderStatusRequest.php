<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\Order\DTO\UpdateOrderStatusDTO;
use Ramsey\Uuid\Uuid;

class UpdateOrderStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_status' => 'required|string|in:pending,confirmed,processing,out_for_delivery,delivered,returned,failed,canceled',
            'payment_status' => 'sometimes|string|in:paid,unpaid,partial,refunded',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'order_status.required' => 'حالة الطلب مطلوبة',
            'order_status.in' => 'حالة الطلب غير صحيحة',
            'payment_status.in' => 'حالة الدفع غير صحيحة',
            'reason.max' => 'سبب التغيير يجب أن يكون أقل من 255 حرف',
            'notes.max' => 'الملاحظات يجب أن تكون أقل من 1000 حرف',
        ];
    }

    public function getOrderId(): string
    {
        return $this->route('id');
    }

    public function getOrderStatus(): string
    {
        return $this->input('order_status');
    }

    public function getPaymentStatus(): ?string
    {
        return $this->input('payment_status');
    }

    public function getReason(): ?string
    {
        return $this->input('reason', 'تحديث حالة الطلب');
    }

    public function getNotes(): ?string
    {
        return $this->input('notes');
    }

    public function createUpdateOrderStatusDTO(): UpdateOrderStatusDTO
    {
        return new UpdateOrderStatusDTO(
            orderId: Uuid::fromString($this->getOrderId()),
            orderStatus: $this->getOrderStatus(),
            paymentStatus: $this->getPaymentStatus(),
            reason: $this->getReason(),
            notes: $this->getNotes()
        );
    }
}
