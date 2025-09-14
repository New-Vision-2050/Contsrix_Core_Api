<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Commands;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class UpdateEcoProductCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $name = null,
        private ?string $description = null,
        private ?float $price = null,
        private ?string $sku = null,
        private ?int $stock = null,
        private ?UuidInterface $warehouseId = null,
        private ?bool $requiresShipping = null,
        private ?bool $unlimitedQuantity = null,
        private ?bool $isTaxable = null,
        private ?bool $priceIncludesVat = null,
        private ?float $vatPercentage = null,
        private ?bool $isVisible = null,
        private ?UuidInterface $categoryId = null,
        private ?UuidInterface $brandId = null,
        private ?UuidInterface $subCategoryId = null,
        private ?string $type = null,
        private ?array $taxes = null,
        private ?array $details = null,
        private ?array $customFields = null,
        private ?array $seo = null,
        private ?array $associatedProductIds = null, // Nullable array for sync
        private ?UploadedFile $mainImage = null,
        private ?array $otherImages = null, // Array of UploadedFile
        private ?array $otherImagesToDelete = null, // Array of UUIDs to delete
        private bool $deleteMainImage = false, // NEW: Default to false
    ) {
    }

    public function getId(): UuidInterface { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getPrice(): ?float { return $this->price; }
    public function getSku(): ?string { return $this->sku; }
    public function getStock(): ?int { return $this->stock; }
    public function getWarehouseId(): ?UuidInterface { return $this->warehouseId; }
    public function getRequiresShipping(): ?bool { return $this->requiresShipping; }
    public function getUnlimitedQuantity(): ?bool { return $this->unlimitedQuantity; }
    public function getIsTaxable(): ?bool { return $this->isTaxable; }
    public function getPriceIncludesVat(): ?bool { return $this->priceIncludesVat; }
    public function getVatPercentage(): ?float { return $this->vatPercentage; }
    public function getIsVisible(): ?bool { return $this->isVisible; }
    public function getCategoryId(): ?UuidInterface { return $this->categoryId; }
    public function getBrandId(): ?UuidInterface { return $this->brandId; }
    public function getSubCategoryId(): ?UuidInterface { return $this->subCategoryId; }
    public function getType(): ?string { return $this->type; }
    public function getTaxes(): ?array { return $this->taxes; }
    public function getDetails(): ?array { return $this->details; }
    public function getCustomFields(): ?array { return $this->customFields; }
    public function getSeo(): ?array { return $this->seo; }
    public function getAssociatedProductIds(): ?array { return $this->associatedProductIds; }
    public function getMainImage(): ?UploadedFile { return $this->mainImage; }
    public function getDeleteMainImage(): bool { return $this->deleteMainImage; }
    public function getOtherImages(): ?array { return $this->otherImages; }
    public function getOtherImagesToDelete(): ?array { return $this->otherImagesToDelete; }
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'sku' => $this->sku,
            'stock' => $this->stock,
            'warehouse_id' => $this->warehouseId?->toString(),
            'requires_shipping' => $this->requiresShipping,
            'unlimited_quantity' => $this->unlimitedQuantity,
            'is_taxable' => $this->isTaxable,
            'price_includes_vat' => $this->priceIncludesVat,
            'vat_percentage' => $this->vatPercentage,
            'is_visible' => $this->isVisible,
            'category_id' => $this->categoryId,
            'sub_category_id' => $this->subCategoryId,
            'type' => $this->type,
        ]);
    }
}
