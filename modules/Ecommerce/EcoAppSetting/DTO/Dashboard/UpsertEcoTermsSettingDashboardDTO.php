<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoTermsSettingDashboardDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly int $show_terms_text = 1,
        public readonly int $show_privacy_policy = 1,
        public readonly int $show_return_policy = 1,
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'show_terms_text' => $this->show_terms_text,
            'show_privacy_policy' => $this->show_privacy_policy,
            'show_return_policy' => $this->show_return_policy,
        ];
    }
}
