<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoDiscount\Models\EcoDiscount;
use App\Traits\HasExport;

/**
 * @property EcoDiscount $model
 * @method EcoDiscount findOneOrFail($id)
 * @method EcoDiscount findOneByOrFail(array $data)
 */
class EcoDiscountRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoDiscount $model)
    {
        parent::__construct($model);
    }

    public function getEcoDiscount(UuidInterface $id): EcoDiscount
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoDiscount(array $data): EcoDiscount
    {
        // Extract product_ids before creating the discount
        $productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);

        // Create the discount
        $discount = $this->create($data);

        // Attach products if provided
        if (!empty($productIds) && $data['applies_to'] === 'specific_products') {
            $discount->products()->attach($productIds);
        }

        // Load the products relationship and return
        return $discount->load('products');
    }

    /**
     * Attach products to a discount
     */
    public function attachProducts(string $discountId, array $productIds): void
    {
        $discount = $this->model->findOrFail($discountId);
        $discount->products()->sync($productIds);
    }

    public function updateEcoDiscount(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoDiscount(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getDiscountStatistics(): array
    {

            $totalDiscounts = $this->model->count();
            $activeDiscounts = $this->model->where('is_active', true)->count();
            $expiredDiscounts = $this->model->where('end_date', '<', now())->count();
            $usedDiscounts = $this->model->where('used_count', '>', 0)->count();

            // Calculate total savings from orders
            $totalSavings = DB::table('eco_orders')
                ->whereNotNull('discount_amount')
                ->sum('discount_amount') ?? 0;

            return [
                [
                    'number' => $totalDiscounts,
                    'title' => 'إجمالي عدد التخفيضات',
                ],
                [
                    'number' => $activeDiscounts,
                    'title' => 'التخفيضات النشطة',
                ],
                [
                    'number' => $expiredDiscounts,
                    'title' => 'التخفيضات المنتهية',
                ],
                [
                    'number' => number_format($totalSavings, 0),
                    'title' => 'إجمالي المدخرات',
                ]
            ];
       
    }

    public function validateAndApplyDiscount(string $code, float $orderAmount, array $productIds = []): array
    {
        $discount = $this->model->where('code', $code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->first();

        if (!$discount) {
            return [
                'success' => false,
                'message' => 'كود الخصم غير صالح أو منتهي الصلاحية'
            ];
        }

        // Check usage limit
        if ($discount->usage_limit && $discount->used_count >= $discount->usage_limit) {
            return [
                'success' => false,
                'message' => 'تم استنفاد عدد مرات استخدام هذا الكود'
            ];
        }

        // Check minimum order amount
        if ($discount->min_order_amount && $orderAmount < $discount->min_order_amount) {
            return [
                'success' => false,
                'message' => 'الحد الأدنى للطلب هو ' . number_format($discount->min_order_amount, 0) . ' ريال'
            ];
        }

        // Check if discount applies to specific products
        if ($discount->applies_to === 'specific_products' && !empty($productIds)) {
            $applicableProducts = $discount->products()->whereIn('eco_products.id', $productIds)->exists();
            if (!$applicableProducts) {
                return [
                    'success' => false,
                    'message' => 'هذا الخصم لا ينطبق على المنتجات المحددة'
                ];
            }
        }

        // Calculate discount amount
        $discountAmount = 0;
        if ($discount->type === 'percentage') {
            $discountAmount = ($orderAmount * $discount->value) / 100;
        } elseif ($discount->type === 'fixed_amount') {
            $discountAmount = $discount->value;
        }

        // Apply maximum discount limit
        if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
            $discountAmount = $discount->max_discount_amount;
        }

        return [
            'success' => true,
            'discount_id' => $discount->id,
            'discount_amount' => $discountAmount,
            'final_amount' => $orderAmount - $discountAmount,
            'discount_name' => $discount->name,
            'discount_code' => $discount->code
        ];
    }
}
