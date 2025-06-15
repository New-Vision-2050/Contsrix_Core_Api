<?php

declare(strict_types=1);

namespace Modules\Subscription\DTO;

class PackageModuleDTO
{
    public function __construct(
        public string $id,
        /** @var PackageFeatureDTO[] */
        public array $features = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'features' => array_map(fn(PackageFeatureDTO $f) => $f->toArray(), $this->features),
        ];
    }
}
