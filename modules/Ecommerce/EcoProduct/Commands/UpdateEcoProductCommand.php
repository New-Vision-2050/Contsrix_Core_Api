<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoProductCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name = null, // Multilingual name is nullable for updates
        private ?array $description = null, // Multilingual description is nullable
        private ?float $price = null,
        private ?string $sku = null,
        private ?int $stock = null,
        private ?UuidInterface $warehouseId = null, // Nullable UuidInterface
        private ?bool $requiresShipping = null,
        private ?bool $unlimitedQuantity = null,
        private ?bool $isTaxable = null,
        private ?bool $priceIncludesVat = null,
        private ?float $vatPercentage = null,
        private ?bool $isVisible = null,
        // Add nullable properties for nested relations
        private ?array $taxes = null,
        private ?array $details = null,
        private ?array $customFields = null,
        private ?array $seo = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?array
    {
        return $this->name;
    }

    public function getDescription(): ?array
    {
        return $this->description;
    }

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
    public function getTaxes(): ?array { return $this->taxes; }
    public function getDetails(): ?array { return $this->details; }
    public function getCustomFields(): ?array { return $this->customFields; }
    public function getSeo(): ?array { return $this->seo; }


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
            'taxes' => $this->taxes,
            'details' => $this->details,
            'custom_fields' => $this->customFields,
            'seo' => $this->seo,
        ]);
    }
}
