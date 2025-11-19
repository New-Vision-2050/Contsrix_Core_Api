<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Services;

use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoProduct\DTO\Dashboard\CreateEcoProductNewDTO;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Exception;

class EcoProductService
{
    /**
     * Create a new product
     */
    public function createProduct(CreateEcoProductNewDTO $dto): EcoProduct
    {
        return DB::transaction(function () use ($dto) {
            // Create the main product
            $product = EcoProduct::create([
                'id' => Uuid::uuid4()->toString(),
                'company_id' => $dto->companyId->toString(),
                'name' => $dto->name,
                'description' => $dto->description,
                'category_id' => $dto->categoryId->toString(),
                'sub_category_id' => $dto->subCategoryId?->toString(),
                'sub_sub_category_id' => $dto->subSubCategoryId?->toString(),
                'brand_id' => $dto->brandId?->toString(),
                'country_ids' => $dto->countryIds,
                'type' => $dto->type,
                'unit' => $dto->unit,
                'sku' => $dto->sku,
                'warehouse_id' => $dto->warehouseId->toString(),
                'gender' => $dto->gender,
                'price' => $dto->price,
                'min_order_quantity' => $dto->minOrderQuantity,
                'stock' => $dto->stock,
                'discount_type' => $dto->discountType,
                'discount_value' => $dto->discountValue,
                'vat_percentage' => $dto->vatPercentage,
                'price_includes_vat' => $dto->priceIncludesVat,
                'shipping_amount' => $dto->shippingAmount,
                'shipping_included_in_price' => $dto->shippingIncludedInPrice,
                'is_visible' => $dto->isVisible,
                'is_featured' => $dto->isFeatured,
                'main_photo' => $dto->mainPhoto,
                'other_photos' => $dto->otherPhotos,
                'meta_title' => $dto->metaTitle,
                'meta_description' => $dto->metaDescription,
                'meta_keywords' => $dto->metaKeywords,
            ]);

            // Sync countries if provided
            if ($dto->countryIds && !empty($dto->countryIds)) {
                $this->syncProductCountries($product, $dto->countryIds);
            }

            // Load relationships for response
            $product->load([
                'company',
                'category',
                'subCategory',
                'subSubCategory',
                'brand',
                'warehouse',
                'countries'
            ]);

            return $product;
        });
    }

    /**
     * Update an existing product
     */
    public function updateProduct(EcoProduct $product, CreateEcoProductNewDTO $dto): EcoProduct
    {
        return DB::transaction(function () use ($product, $dto) {
            // Update the product
            $product->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'category_id' => $dto->categoryId->toString(),
                'sub_category_id' => $dto->subCategoryId?->toString(),
                'sub_sub_category_id' => $dto->subSubCategoryId?->toString(),
                'brand_id' => $dto->brandId?->toString(),
                'country_ids' => $dto->countryIds,
                'type' => $dto->type,
                'unit' => $dto->unit,
                'sku' => $dto->sku,
                'warehouse_id' => $dto->warehouseId->toString(),
                'gender' => $dto->gender,
                'price' => $dto->price,
                'min_order_quantity' => $dto->minOrderQuantity,
                'stock' => $dto->stock,
                'discount_type' => $dto->discountType,
                'discount_value' => $dto->discountValue,
                'vat_percentage' => $dto->vatPercentage,
                'price_includes_vat' => $dto->priceIncludesVat,
                'shipping_amount' => $dto->shippingAmount,
                'shipping_included_in_price' => $dto->shippingIncludedInPrice,
                'is_visible' => $dto->isVisible,
                'is_featured' => $dto->isFeatured,
                'main_photo' => $dto->mainPhoto ?? $product->main_photo,
                'other_photos' => $dto->otherPhotos ?? $product->other_photos,
                'meta_title' => $dto->metaTitle,
                'meta_description' => $dto->metaDescription,
                'meta_keywords' => $dto->metaKeywords,
            ]);

            // Sync countries if provided
            if ($dto->countryIds !== null) {
                $this->syncProductCountries($product, $dto->countryIds);
            }

            // Load relationships for response
            $product->load([
                'company',
                'category',
                'subCategory',
                'subSubCategory',
                'brand',
                'warehouse',
                'countries'
            ]);

            return $product;
        });
    }

    /**
     * Get product with all relationships
     */
    public function getProductWithRelations(string $id): ?EcoProduct
    {
        return EcoProduct::with([
            'company',
            'category',
            'subCategory',
            'subSubCategory',
            'brand',
            'warehouse',
            'countries'
        ])->find($id);
    }

    /**
     * Get paginated products for company
     */
    public function getCompanyProducts(string $companyId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return EcoProduct::where('company_id', $companyId)
            ->with([
                'category',
                'subCategory',
                'brand',
                'warehouse'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete product
     */
    public function deleteProduct(EcoProduct $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Delete associated countries
            $product->countries()->detach();
            
            // Delete the product
            return $product->delete();
        });
    }

    /**
     * Sync product countries
     */
    private function syncProductCountries(EcoProduct $product, array $countryIds): void
    {
        // Remove existing relationships
        DB::table('product_countries')
            ->where('product_id', $product->id)
            ->delete();

        // Add new relationships
        if (!empty($countryIds)) {
            $data = [];
            foreach ($countryIds as $countryId) {
                $data[] = [
                    'product_id' => $product->id,
                    'country_id' => $countryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('product_countries')->insert($data);
        }
    }

    /**
     * Calculate final price with discounts
     */
    public function calculateFinalPrice(EcoProduct $product): float
    {
        $basePrice = $product->price;

        if (!$product->discount_type || !$product->discount_value) {
            return $basePrice;
        }

        if ($product->discount_type === 'percentage') {
            $discount = ($basePrice * $product->discount_value) / 100;
        } else {
            $discount = $product->discount_value;
        }

        return max(0, $basePrice - $discount);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(EcoProduct $product): bool
    {
        // If stock is null, consider it as unlimited
        if ($product->stock === null) {
            return true;
        }

        return $product->stock > 0;
    }

    /**
     * Get product statistics for company
     */
    public function getProductStatistics(string $companyId): array
    {
        $products = EcoProduct::where('company_id', $companyId);

        return [
            'total_products' => $products->count(),
            'active_products' => $products->where('is_visible', true)->count(),
            'products_in_stock' => $products->whereNotNull('stock')->where('stock', '>', 0)->count(),
            'low_stock_products' => $products->whereNotNull('stock')->where('stock', '<=', 10)->where('stock', '>', 0)->count(),
            'out_of_stock_products' => $products->where('stock', 0)->count(),
            'digital_products' => $products->where('type', 'digital')->count(),
            'normal_products' => $products->where('type', 'normal')->count(),
        ];
    }
}
