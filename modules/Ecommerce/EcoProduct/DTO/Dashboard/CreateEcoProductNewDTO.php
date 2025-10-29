<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class CreateEcoProductNewDTO
{
    public function __construct(
        public readonly UuidInterface $companyId,
        // Multilingual fields
        public readonly array $name,                    // {"ar": "اسم المنتج", "en": "Product Name"}
        public readonly ?array $description,            // {"ar": "الوصف", "en": "Description"}
        
        // Categories and Brand
        public readonly UuidInterface $categoryId,
        public readonly ?UuidInterface $subCategoryId,
        public readonly ?UuidInterface $subSubCategoryId,
        public readonly ?UuidInterface $brandId,
        
        // Countries
        public readonly ?array $countryIds,             // ["uuid1", "uuid2", ...]
        
        // Product specifications
        public readonly string $type,                   // "digital" or "normal"
        public readonly ?string $unit,                  // "kg", "m", "liter", "gram", "piece" (nullable for digital products)
        public readonly string $sku,
        public readonly UuidInterface $warehouseId,
        public readonly string $gender,                 // "male", "female", "all"
        
        // Pricing and quantities
        public readonly float $price,
        public readonly int $minOrderQuantity,
        public readonly ?int $stock,
        
        // Discount system
        public readonly ?string $discountType,          // "amount" or "percentage"
        public readonly ?float $discountValue,
        
        // Tax and shipping
        public readonly ?float $vatPercentage,
        public readonly bool $priceIncludesVat,
        public readonly ?float $shippingAmount,
        public readonly bool $shippingIncludedInPrice,
        
        // Visibility
        public readonly bool $isVisible,
        
        // Media
        public readonly ?array $mainPhoto,              // Photo data
        public readonly ?array $otherPhotos,            // Array of photo data
        
        // Video
        public readonly ?string $videoUrl,              // Video URL
        
        // SEO
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?string $metaKeywords,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->categoryId->toString(),
            'sub_category_id' => $this->subCategoryId?->toString(),
            'sub_sub_category_id' => $this->subSubCategoryId?->toString(),
            'brand_id' => $this->brandId?->toString(),
            'country_ids' => $this->countryIds,
            'type' => $this->type,
            'unit' => $this->unit,
            'sku' => $this->sku,
            'warehouse_id' => $this->warehouseId->toString(),
            'gender' => $this->gender,
            'price' => $this->price,
            'min_order_quantity' => $this->minOrderQuantity,
            'stock' => $this->stock,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'vat_percentage' => $this->vatPercentage,
            'price_includes_vat' => $this->priceIncludesVat,
            'shipping_amount' => $this->shippingAmount,
            'shipping_included_in_price' => $this->shippingIncludedInPrice,
            'is_visible' => $this->isVisible,
            'main_photo' => $this->mainPhoto,
            'other_photos' => $this->otherPhotos,
            'video_url' => $this->videoUrl,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'meta_keywords' => $this->metaKeywords,
        ];
    }
}
