<?php declare(strict_types=1);

namespace Modules\Subscription\Package\DTO;

class PackageFeatureDTO
{
    public function __construct(
        public string $permissionId,
        public ?int $limit = null,
    ) {}

    public function toArray(): array
    {
        return [
            'permission_id' => $this->permissionId,
            'limit' => $this->limit,
        ];
    }
}
