<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\DTO;

class AttachPackageFeaturesDTO
{
    /**
     * @param array<PackageFeatureDTO> $features
     */
    public function __construct(
        public string $packageId,
        public array $features,
    ) {}

    public function toArray(): array
    {
        return [
            'package_id' => $this->packageId,
            'features' => array_map(fn(PackageFeatureDTO $f) => $f->toArray(), $this->features),
        ];
    }
}
