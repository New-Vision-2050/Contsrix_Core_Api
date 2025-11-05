<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Order\DTO\CreateOrderDTO;
use Modules\Ecommerce\Order\DTO\UpdateOrderStatusDTO;
use Modules\Ecommerce\Order\Models\Order;
use Modules\Ecommerce\Order\Repositories\OrderRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

class OrderCRUDService
{
    use HasExportService;

    public function __construct(
        private OrderRepository $repository,
    ) {
    }

    public function create(CreateOrderDTO $createOrderDTO): Order
    {
        // Start with basic data from DTO
        $orderData = $createOrderDTO->toArray();
        
        // Complete dynamic data
        $this->completeDynamicOrderData($orderData, $createOrderDTO);
        
        // Generate order serial and number
        $this->generateOrderSerial($orderData);
        
        // Calculate order totals
        $calculatedTotals = $this->calculateOrderTotals($createOrderDTO->getOrderItems(), $orderData);
        $orderData = array_merge($orderData, $calculatedTotals);
        
        // Create the order
        $order = $this->repository->createOrder($orderData);
        
        // Create order items with complete details
        $this->createOrderItems($order, $createOrderDTO->getOrderItems());
        
        // Create initial status history
        $this->createInitialStatusHistory($order);
        
        // Load relationships and return
        return $order->load(['orderDetails.warehouse', 'statusHistories', 'customer']);
    }

    private function completeDynamicOrderData(array &$orderData, CreateOrderDTO $dto): void
    {
        // Complete missing order fields dynamically
        $orderData['company_id'] = $dto->companyId->toString();
        $orderData['customer_type'] = $this->determineCustomerType($dto->isGuest, $dto->customerId);
        $orderData['payment_status'] = 'unpaid';
        $orderData['order_status'] = 'pending';
        $orderData['transaction_ref'] = null;
        $orderData['payment_by'] = null;
        $orderData['payment_note'] = null;
        $orderData['bring_change_amount'] = 0.00;
        $orderData['bring_change_amount_currency'] = 'SAR';
        $orderData['is_pause'] = '0';
        $orderData['cause'] = null;
        $orderData['discount_type'] = 'percentage';
        $orderData['coupon_code'] = null;
        $orderData['coupon_discount_bearer'] = 'inhouse';
        $orderData['shipping_responsibility'] = null;
        $orderData['shipping_method_id'] = null;
        $orderData['is_shipping_free'] = false;
        $orderData['order_group_id'] = $this->generateOrderGroupId();
        $orderData['verification_code'] = '0';
        $orderData['verification_status'] = false;
        $orderData['shipping_address_data'] = json_encode(['address' => $dto->shippingAddress]);
        $orderData['delivery_man_id'] = null;
        $orderData['deliveryman_charge'] = 0.00;
        $orderData['expected_delivery_date'] = $this->calculateExpectedDeliveryDate();
        $orderData['billing_address'] = null; // Will be set separately if needed
        $orderData['billing_address_data'] = json_encode(['address' => $dto->shippingAddress]);
        $orderData['order_type'] = 'default_type';
        $orderData['extra_discount'] = 0.00;
        $orderData['extra_discount_type'] = 'percentage';
        $orderData['refer_and_earn_discount'] = 0.00;
        $orderData['free_delivery_bearer'] = 'admin';
        $orderData['checked'] = false;
        $orderData['shipping_type'] = 'home_delivery';
        $orderData['delivery_type'] = 'normal';
        $orderData['delivery_service_name'] = null;
        $orderData['third_party_delivery_tracking_id'] = null;
    }

    private function determineWarehouseForProduct(string $productId): ?string
    {
        try {
            // Get warehouse from the specific product
            $product = EcoProduct::find($productId);
            if ($product && $product->warehouse_id) {
                return $product->warehouse_id;
            }
            
            // Fallback to company's default warehouse
            $defaultWarehouse = \Modules\Ecommerce\Warehous\Models\Warehous::where('company_id', tenant('id'))
                ->where('is_default', true)
                ->first();
            
            if ($defaultWarehouse) {
                return $defaultWarehouse->id;
            }
            
            // Last fallback - any warehouse for this company
            $anyWarehouse = \Modules\Ecommerce\Warehous\Models\Warehous::where('company_id', tenant('id'))
                ->first();
            
            return $anyWarehouse?->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function determineCustomerType(bool $isGuest, ?string $customerId): string
    {
        if ($isGuest || !$customerId) {
            return 'guest';
        }
        
        return 'registered';
    }

    private function calculateExpectedDeliveryDate(): ?string
    {
        // Default delivery time: 3-5 business days
        $deliveryDays = 3;
        $expectedDate = now()->addDays($deliveryDays);
        
        // Skip weekends (Friday-Saturday in Saudi Arabia)
        while ($expectedDate->isFriday() || $expectedDate->isSaturday()) {
            $expectedDate->addDay();
        }
        
        return $expectedDate->format('Y-m-d');
    }

    private function generateOrderGroupId(): string
    {
        // Generate order group ID: OG-YYYY-000001
        $lastOrder = Order::orderBy('created_at', 'desc')->first();
        $nextNumber = 1;
        
        if ($lastOrder && $lastOrder->order_group_id && preg_match('/OG-\d{4}-(\d{6})/', $lastOrder->order_group_id, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }
        
        $year = date('Y');
        return sprintf('OG-%s-%06d', $year, $nextNumber);
    }

    private function createOrderItems(Order $order, array $orderItems): void
    {
        foreach ($orderItems as $item) {
            // Get complete product details
            $productDetails = $this->getProductDetails($item['product_id']);

            // Calculate pricing automatically
            $calculatedPricing = $this->calculateItemPricing($productDetails, $item);
            
            // Complete order detail data dynamically
            $orderDetailData = $this->completeDynamicOrderDetailData($order, $item, $productDetails, $calculatedPricing);
            
            $order->orderDetails()->create($orderDetailData);
            
            // Decrease stock if needed
            $this->decreaseProductStock($item['product_id'], $item['quantity']);
        }
    }

    private function decreaseProductStock(string $productId, int $quantity): void
    {
        try {
            $product = EcoProduct::find($productId);
            if ($product && $product->quantity !== null && $product->quantity >= $quantity) {
                $product->decrement('quantity', $quantity);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            Log::warning("Failed to decrease stock for product {$productId}: " . $e->getMessage());
        }
    }

    private function completeDynamicOrderDetailData(Order $order, array $item, array $productDetails, array $calculatedPricing): array
    {
        return [
            // Basic required fields
            'company_id' => $order->company_id,
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'warehouse_id' => $this->determineWarehouseForProduct($item['product_id']),
            'qty' => $item['quantity'],
            'price' => $calculatedPricing['unit_price'],
            'tax' => $calculatedPricing['tax_amount'],
            'discount' => $calculatedPricing['discount_amount'],
            
            // Dynamic fields with defaults
            'digital_file_after_sell' => null,
            'product_details' => json_encode([
                'product_name' => $productDetails['name'],
                'product_sku' => $productDetails['sku'],
                'original_price' => $productDetails['price'],
                'unit_price' => $calculatedPricing['unit_price'],
                'total_price' => $calculatedPricing['total_price'],
                'description' => $productDetails['description'],
                'category' => $productDetails['category'],
                'brand' => $productDetails['brand'],
                'warehouse' => $productDetails['warehouse'],
                'discount_percentage' => $calculatedPricing['discount_percentage'],
                'tax_percentage' => $calculatedPricing['tax_percentage'],
            ]),
            'tax_model' => 'include',
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_method_id' => null,
            'variant' => null,
            'variation' => null,
            'discount_type' => 'percentage',
            'is_stock_decreased' => 1,
            'refund_request' => 0,
        ];
    }

    private function generateOrderSerial(array &$orderData): void
    {
        // Get the next order number from the Order model
        $lastOrder = Order::orderBy('order_number', 'desc')->first();
        $nextNumber = $lastOrder ? $lastOrder->order_number + 1 : 1;
        
        // Generate serial format: ORD-YYYY-000001
        $year = date('Y');
        $orderSerial = sprintf('ORD-%s-%06d', $year, $nextNumber);
        
        // Add to order data array
        $orderData['order_number'] = $nextNumber;
        $orderData['order_serial'] = $orderSerial;
    }

    private function calculateOrderTotals(array $orderItems, array $orderData): array
    {
        $totalDiscount = 0;
        $totalTax = 0;
        $subtotal = 0;
        $shippingCost = 0;
        $requiresShipping = false;

        // Calculate totals from all order items
        foreach ($orderItems as $item) {
            $productDetails = $this->getProductDetails($item['product_id']);
            $calculatedPricing = $this->calculateItemPricing($productDetails, $item);
            
            $subtotal += $calculatedPricing['unit_price'] * $item['quantity'];
            $totalDiscount += $calculatedPricing['discount_amount'];
            $totalTax += $calculatedPricing['tax_amount'];
            
            // Check if any product requires shipping
            if ($this->productRequiresShipping($item['product_id'])) {
                $requiresShipping = true;
            }
        }

        // Calculate shipping cost
        if ($requiresShipping) {
            $shippingCost = $this->calculateShippingCost($orderData, $subtotal);
        }

        // Calculate final order amount
        $orderAmount = $subtotal - $totalDiscount + $totalTax + $shippingCost;

        return [
            'order_amount' => round($orderAmount, 2),
            'paid_amount' => 0.00, // Default unpaid
            'discount_amount' => round($totalDiscount, 2),
            'shipping_cost' => round($shippingCost, 2),
        ];
    }

    private function productRequiresShipping(string $productId): bool
    {
        try {
            $product = EcoProduct::find($productId);
            return $product ? ($product->requires_shipping ?? true) : true;
        } catch (\Exception $e) {
            return true; // Default to requiring shipping
        }
    }

    private function calculateShippingCost(array $orderData, float $subtotal): float
    {
        // Basic shipping calculation logic
        // You can customize this based on your business rules
        
        // Free shipping threshold (e.g., orders over 500 SAR)
        $freeShippingThreshold = 500.00;
        if ($subtotal >= $freeShippingThreshold) {
            return 0.00;
        }

        // Default shipping cost
        $baseShippingCost = 25.00;
        
        // Check if warehouse has specific shipping rates
        if (isset($orderData['warehouse_id'])) {
            $warehouseShipping = $this->getWarehouseShippingCost($orderData['warehouse_id']);
            if ($warehouseShipping > 0) {
                return $warehouseShipping;
            }
        }

        return $baseShippingCost;
    }

    private function getWarehouseShippingCost(string $warehouseId): float
    {
        try {
            // You can implement warehouse-specific shipping costs here
            // For now, return 0 to use default shipping cost
            return 0.00;
        } catch (\Exception $e) {
            return 0.00;
        }
    }

    private function calculateItemPricing(array $productDetails, array $item): array
    {
        // Get base price from product
        $originalPrice = (float) $productDetails['price'];
        $quantity = (int) $item['quantity'];
        
        // Use provided unit_price or fall back to product price
        $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : $originalPrice;
        
        // Calculate discount
        $discountAmount = $this->calculateDiscount($unitPrice, $quantity, $item, $productDetails);
        
        // Calculate price after discount
        $priceAfterDiscount = ($unitPrice * $quantity) - $discountAmount;
        
        // Calculate tax
        $taxAmount = $this->calculateTax($priceAfterDiscount, $item, $productDetails);
        
        // Calculate total price
        $totalPrice = $priceAfterDiscount + $taxAmount;
        
        return [
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'discount_percentage' => $originalPrice > 0 ? (($discountAmount / ($unitPrice * $quantity)) * 100) : 0,
            'tax_percentage' => $priceAfterDiscount > 0 ? (($taxAmount / $priceAfterDiscount) * 100) : 0,
        ];
    }

    private function calculateDiscount(float $unitPrice, int $quantity, array $item, array $productDetails): float
    {
        // If discount amount is provided, use it
        if (isset($item['discount_amount']) && $item['discount_amount'] > 0) {
            return (float) $item['discount_amount'];
        }
        
        // If discount percentage is provided, calculate amount
        if (isset($item['discount_percentage']) && $item['discount_percentage'] > 0) {
            $discountPercentage = (float) $item['discount_percentage'];
            return ($unitPrice * $quantity) * ($discountPercentage / 100);
        }
        
        // Check if product has active discount
        $productDiscount = $this->getProductDiscount($item['product_id']);
        if ($productDiscount > 0) {
            return ($unitPrice * $quantity) * ($productDiscount / 100);
        }
        
        // Apply bulk discount rules
        $bulkDiscount = $this->getBulkDiscount($quantity, $unitPrice);
        if ($bulkDiscount > 0) {
            return $bulkDiscount;
        }
        
        return 0;
    }

    private function calculateTax(float $priceAfterDiscount, array $item, array $productDetails): float
    {
        // If tax amount is provided, use it
        if (isset($item['tax_amount']) && $item['tax_amount'] > 0) {
            return (float) $item['tax_amount'];
        }
        
        // If tax percentage is provided, calculate amount
        if (isset($item['tax_percentage']) && $item['tax_percentage'] > 0) {
            $taxPercentage = (float) $item['tax_percentage'];
            return $priceAfterDiscount * ($taxPercentage / 100);
        }
        
        // Get product tax configuration
        $productTaxRate = $this->getProductTaxRate($item['product_id']);
        if ($productTaxRate > 0) {
            return $priceAfterDiscount * ($productTaxRate / 100);
        }
        
        // Default VAT rate (15% for Saudi Arabia)
        $defaultVatRate = 15.0;
        return $priceAfterDiscount * ($defaultVatRate / 100);
    }

    private function getProductDiscount(string $productId): float
    {
        try {
            $product = EcoProduct::find($productId);
            
            if (!$product) {
                return 0;
            }
            
            // Check if product has active discount
            if ($product->discount_amount > 0 && $this->isDiscountActive($product)) {
                if ($product->discount_type === 'percentage') {
                    return (float) $product->discount_amount;
                } else {
                    // Convert fixed amount to percentage
                    return $product->price > 0 ? (($product->discount_amount / $product->price) * 100) : 0;
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getProductTaxRate(string $productId): float
    {
        try {
            // Get product taxes from product_taxes table
            $productTaxes = DB::table('product_taxes')
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->sum('tax_rate');
            
            return (float) $productTaxes;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getBulkDiscount(int $quantity, float $unitPrice): float
    {
        // Bulk discount rules
        $bulkRules = [
            ['min_quantity' => 10, 'discount_percentage' => 5],   // 5% discount for 10+ items
            ['min_quantity' => 20, 'discount_percentage' => 10],  // 10% discount for 20+ items
            ['min_quantity' => 50, 'discount_percentage' => 15],  // 15% discount for 50+ items
        ];
        
        $discountPercentage = 0;
        foreach ($bulkRules as $rule) {
            if ($quantity >= $rule['min_quantity']) {
                $discountPercentage = $rule['discount_percentage'];
            }
        }
        
        return $discountPercentage > 0 ? ($unitPrice * $quantity) * ($discountPercentage / 100) : 0;
    }

    private function isDiscountActive($product): bool
    {
        $now = now();
        
        // Check if discount has start date and it's in the future
        if ($product->discount_start_date && $now->lt($product->discount_start_date)) {
            return false;
        }
        
        // Check if discount has end date and it's in the past
        if ($product->discount_end_date && $now->gt($product->discount_end_date)) {
            return false;
        }
        
        return true;
    }

    private function createInitialStatusHistory(Order $order): void
    {
        $order->statusHistories()->create([
            'company_id' => $order->company_id,
            'user_type' => auth()->check() ? 'admin' : 'system',
            'status' => $order->order_status,
            'previous_status' => null,
            'changed_by' => auth()->id(),
            'reason' => 'إنشاء طلب جديد',
            'notes' => 'تم إنشاء الطلب بنجاح',
            'changed_at' => now(),
        ]);
    }


    private function getProductDetails(string $productId): array
    {
        $product = EcoProduct::with(['category', 'brand', 'warehouse'])->find($productId);
        
        if (!$product) {
            throw new \InvalidArgumentException("Product with ID {$productId} not found");
        }
        
        return [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price ?? 0,
            'description' => $product->description,
            'category' => $product->category?->name,
            'brand' => $product->brand?->name,
            'warehouse' => $product->warehouse?->name,
        ];
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Order
    {
        return $this->repository->getOrder(
            id: $id,
        );
    }

    public function updateStatus(string $orderId, string $orderStatus, ?string $paymentStatus = null, ?string $reason = null, ?string $notes = null): Order
    {
        $order = $this->repository->getOrder(\Ramsey\Uuid\Uuid::fromString($orderId));
        
        // Store previous status for history
        $previousOrderStatus = $order->order_status;
        $previousPaymentStatus = $order->payment_status;
        
        // Update order status
        $updateData = ['order_status' => $orderStatus];
        
        if ($paymentStatus) {
            $updateData['payment_status'] = $paymentStatus;
        }
        
        // Update the order directly
        $order->update($updateData);
        $order->refresh();
        
        // Create status history for order status change
        if ($previousOrderStatus !== $orderStatus) {
            $this->createStatusHistory($order, $orderStatus, $previousOrderStatus, $reason, $notes);
        }
        
        // Create status history for payment status change if applicable
        if ($paymentStatus && $previousPaymentStatus !== $paymentStatus) {
            $this->createStatusHistory($order, $paymentStatus, $previousPaymentStatus, $reason ?? 'تحديث حالة الدفع', $notes, 'payment');
        }
        
        return $order->load(['orderDetails', 'statusHistories', 'warehouse', 'customer']);
    }

    private function createStatusHistory(Order $order, string $status, ?string $previousStatus, ?string $reason, ?string $notes, string $type = 'order'): void
    {
        $order->statusHistories()->create([
            'company_id' => $order->company_id,
            'user_type' => auth()->check() ? 'admin' : 'system',
            'status' => $status,
            'previous_status' => $previousStatus,
            'changed_by' => auth()->id(),
            'reason' => $reason ?? ($type === 'payment' ? 'تحديث حالة الدفع' : 'تحديث حالة الطلب'),
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }

    public function bulkUpdateStatus(array $orderIds, string $orderStatus, ?string $reason = null, ?string $notes = null): array
    {
        $updatedOrders = [];
        
        foreach ($orderIds as $orderId) {
            try {
                $updatedOrders[] = $this->updateStatus($orderId, $orderStatus, null, $reason, $notes);
            } catch (\Exception $e) {
                // Log error but continue with other orders
                \Log::error("Failed to update order {$orderId}: " . $e->getMessage());
            }
        }
        
        return $updatedOrders;
    }

    public function updateStatusFromDTO(UpdateOrderStatusDTO $dto): Order
    {
        return $this->updateStatus(
            $dto->getOrderId()->toString(),
            $dto->getOrderStatus(),
            $dto->getPaymentStatus(),
            $dto->getReason(),
            $dto->getNotes()
        );
    }

    public static function getOrdersStatistics(): array
    {
        // Get total orders count
        $totalOrders = Order::count();
        // Get delivered orders
        $deliveredOrders = Order::where('order_status', 'delivered')->count();
        // Get pending orders
        $pendingOrders = Order::where('order_status', 'pending')->count();
        // Get canceled orders
        $canceledOrders = Order::where('order_status', 'canceled')->count();

        return [
            [
                'number' => $totalOrders,
                'title' => 'إجمالي الطلبات'
            ],
            [
                'number' => $deliveredOrders,
                'title' => 'الطلبات المستلمة'
            ],
            [
                'number' => $pendingOrders,
                'title' => 'الطلبات المعلقة'
            ],
            [
                'number' => $canceledOrders,
                'title' => 'الطلبات الملغية'
            ]
        ];
    }
}
