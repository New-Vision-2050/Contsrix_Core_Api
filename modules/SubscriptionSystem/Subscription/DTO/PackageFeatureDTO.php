<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\DTO;

use Modules\SubscriptionSystem\Subscription\Enums\FeatureLimitTypeEnum;

class PackageFeatureDTO
{
    public function __construct(
        public string $id,
        public bool $is_enabled,
        public ?int $limit = null,
        public ?FeatureLimitTypeEnum $limit_type = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'is_enabled' => $this->is_enabled,
            'limit' => $this->limit,
            'limit_type' => $this->limit_type?->value,
        ];
    }
}
