<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Services\Customer;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;
use Ramsey\Uuid\UuidInterface;

class EcoProductCustomerService
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function list(
        int $page = 1, 
        int $perPage = 12,
        ?string $categoryId = null,
        ?string $brandId = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): array {
        return $this->repository->paginatedVisible(
            page: $page,
            perPage: $perPage,
            categoryId: $categoryId,
            brandId: $brandId,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            sortBy: $sortBy,
            sortDirection: $sortDirection
        );
    }

    public function get(UuidInterface $id): EcoProduct
    {
        return $this->repository->getVisibleEcoProduct(id: $id);
    }

    public function search(string $query, int $page = 1, int $perPage = 12): array
    {
        return $this->repository->searchVisible(
            query: $query,
            page: $page,
            perPage: $perPage
        );
    }

    public function getByCategory(UuidInterface $categoryId, int $page = 1, int $perPage = 12): array
    {
        return $this->repository->getByCategoryVisible(
            categoryId: $categoryId,
            page: $page,
            perPage: $perPage
        );
    }

    public function getFeatured(int $limit = 8): Collection
    {
        return $this->repository->getFeaturedVisible(limit: $limit);
    }

    public function getRelated(UuidInterface $productId, int $limit = 6): Collection
    {
        return $this->repository->getRelatedVisible(
            productId: $productId,
            limit: $limit
        );
    }

    public function getNewArrivals(int $limit = 8): Collection
    {
        return $this->repository->getNewArrivalsVisible(limit: $limit);
    }

    public function getBestSellers(int $limit = 8): Collection
    {
        return $this->repository->getBestSellersVisible(limit: $limit);
    }

    public function getOnSale(int $page = 1, int $perPage = 12): array
    {
        return $this->repository->getOnSaleVisible(
            page: $page,
            perPage: $perPage
        );
    }

    public function checkAvailability(UuidInterface $productId, int $quantity = 1): bool
    {
        $product = $this->get($productId);
        
        if ($product->unlimited_quantity) {
            return true;
        }

        return $product->stock >= $quantity;
    }

    public function getPrice(UuidInterface $productId): array
    {
        $product = $this->get($productId);
        
        $originalPrice = $product->price;
        $discountedPrice = $originalPrice;
        $hasDiscount = false;

        if ($product->has_discount && $this->isDiscountActive($product)) {
            $hasDiscount = true;
            
            if ($product->discount_percentage) {
                $discountAmount = ($originalPrice * $product->discount_percentage) / 100;
                if ($product->max_discount_amount) {
                    $discountAmount = min($discountAmount, $product->max_discount_amount);
                }
                $discountedPrice = $originalPrice - $discountAmount;
            } elseif ($product->discount_amount) {
                $discountedPrice = max(0, $originalPrice - $product->discount_amount);
            }
        }

        return [
            'original_price' => $originalPrice,
            'discounted_price' => $discountedPrice,
            'discount_amount' => $originalPrice - $discountedPrice,
            'discount_percentage' => $hasDiscount ? (($originalPrice - $discountedPrice) / $originalPrice) * 100 : 0,
            'has_discount' => $hasDiscount,
        ];
    }

    private function isDiscountActive(EcoProduct $product): bool
    {
        $now = now();
        
        if ($product->discount_start_date && $now->lt($product->discount_start_date)) {
            return false;
        }
        
        if ($product->discount_end_date && $now->gt($product->discount_end_date)) {
            return false;
        }
        
        return true;
    }
}
