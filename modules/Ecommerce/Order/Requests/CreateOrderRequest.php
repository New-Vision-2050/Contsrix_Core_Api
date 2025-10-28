<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Order\DTO\CreateOrderDTO;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|uuid|exists:users,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'is_guest' => 'sometimes|boolean',
            'shipping_address' => 'nullable|string',
            'order_note' => 'nullable|string',
            'expected_delivery_date' => 'nullable|date|after:today',
            
            // Order items validation
            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|uuid|exists:eco_products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.uuid' => 'معرف العميل يجب أن يكون UUID صحيح',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            'warehouse_id.required' => 'معرف المخزن مطلوب',
            'warehouse_id.uuid' => 'معرف المخزن يجب أن يكون UUID صحيح',
            'warehouse_id.exists' => 'المخزن المحدد غير موجود',
            'is_guest.boolean' => 'حقل الضيف يجب أن يكون صحيح أو خطأ',
            'expected_delivery_date.date' => 'تاريخ التوصيل المتوقع يجب أن يكون تاريخ صحيح',
            'expected_delivery_date.after' => 'تاريخ التوصيل المتوقع يجب أن يكون بعد اليوم',
            
            // Order items messages
            'order_items.required' => 'عناصر الطلب مطلوبة',
            'order_items.array' => 'عناصر الطلب يجب أن تكون مصفوفة',
            'order_items.min' => 'يجب أن يحتوي الطلب على عنصر واحد على الأقل',
            'order_items.*.product_id.required' => 'معرف المنتج مطلوب',
            'order_items.*.product_id.uuid' => 'معرف المنتج يجب أن يكون UUID صحيح',
            'order_items.*.product_id.exists' => 'المنتج المحدد غير موجود',
            'order_items.*.quantity.required' => 'الكمية مطلوبة',
            'order_items.*.quantity.integer' => 'الكمية يجب أن تكون رقم صحيح',
            'order_items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
        ];
    }

    private function getCompanyId(): string
    {
        // Try to get company ID from multiple sources
        
        // 1. From tenant context (if tenancy is initialized)
        if (function_exists('tenant') && tenant('id')) {
            return tenant('id');
        }
        
        // 2. From request input (if provided)
        if ($this->input('company_id')) {
            return $this->input('company_id');
        }
        
        // 3. From authenticated user's company (if available)
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }
        
        // 4. From header (if provided)
        if ($this->header('X-Company-Id')) {
            return $this->header('X-Company-Id');
        }
        
        // 5. Default fallback - you might want to change this
        throw new \InvalidArgumentException('معرف الشركة مطلوب - Company ID is required. Please ensure tenancy is properly initialized or provide company_id.');
    }

    public function createCreateOrderDTO(): CreateOrderDTO
    {
        // Get company ID from tenant context
        $companyId = $this->getCompanyId();

        // Auto-determine customer type based on guest status and customer_id
        $isGuest = $this->input('is_guest', false);
        $customerId = $this->input('customer_id');
        $customerType = $this->determineCustomerType($isGuest, $customerId);

        return new CreateOrderDTO(
            companyId: Uuid::fromString($companyId),
            customerId: $customerId ? Uuid::fromString($customerId) : null,
            warehouseId: Uuid::fromString($this->input('warehouse_id')), // Required now
            isGuest: $isGuest,
            customerType: $customerType, // Auto-determined
            paymentStatus: 'unpaid', // Default
            orderStatus: 'pending', // Default
            paymentMethod: null, // Will be set during payment
            orderAmount: 0, // Will be calculated automatically
            paidAmount: 0, // Will be calculated automatically
            discountAmount: 0, // Will be calculated automatically
            shippingCost: 0, // Will be calculated automatically
            shippingAddress: $this->input('shipping_address'),
            orderNote: $this->input('order_note'),
            expectedDeliveryDate: $this->input('expected_delivery_date'),
            orderItems: $this->input('order_items', []),
        );
    }

    private function determineCustomerType(bool $isGuest, ?string $customerId): string
    {
        if ($isGuest || !$customerId) {
            return 'guest';
        }
        
        return 'registered';
    }
}
