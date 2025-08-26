<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoProductDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public array $name,
        public ?array $description,
        public float $price,
        public string $sku,
        public ?int $stock,
        public UuidInterface $warehouseId,
        public bool $requiresShipping = false,
        public bool $unlimitedQuantity = false,
        public bool $isTaxable = true,
        public bool $priceIncludesVat = false,
        public ?float $vatPercentage,
        public bool $isVisible = true,
        public ?array $details = null,
        public ?array $customFields = null,
        public ?array $seo = null,
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
        ]);
    }
}
