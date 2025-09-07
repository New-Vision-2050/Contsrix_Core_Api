<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\DTO;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class CreateEcoProductDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $name,
        public ?string $description,
        public float $price,
        public string $sku,
        public ?int $stock,
        public UuidInterface $warehouseId,
        public bool $requiresShipping,
        public bool $unlimitedQuantity,
        public bool $isTaxable,
        public bool $priceIncludesVat,
        public ?float $vatPercentage,
        public bool $isVisible,
        public ?UuidInterface $brandId,
        public ?UuidInterface $categoryId,
        public ?UuidInterface $subCategoryId,
        public ?string $type,
        public ?array $taxes = null,
        public ?array $details = null,
        public ?array $customFields = null,
        public ?array $seo = null,
        public array $associatedProductIds = [],
        public UploadedFile $mainImage, // Single UploadedFile
        public array $otherImages = [], //Array of UploadedFile
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId->toString(),
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'sku' => $this->sku,
            'stock' => $this->stock,
            'warehouse_id' => $this->warehouseId->toString(),
            'requires_shipping' => $this->requiresShipping,
            'unlimited_quantity' => $this->unlimitedQuantity,
            'is_taxable' => $this->isTaxable,
            'price_includes_vat' => $this->priceIncludesVat,
            'vat_percentage' => $this->vatPercentage,
            'is_visible' => $this->isVisible,
            'category_id' => $this->categoryId,
            'sub_category_id' => $this->subCategoryId,
            'type' => $this->type,
            'brand_id' => $this->brandId,
            'taxes' => $this->taxes,
            'details' => $this->details,
            'custom_fields' => $this->customFields,
            'seo' => $this->seo,
            'associated_product_ids' => $this->associatedProductIds,
            'main_image' => $this->mainImage,
            'other_images' => $this->otherImages

        ]);
    }
}
