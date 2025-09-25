<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoAppSettingFrontPageDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly int $show_logo_on_first_page,
        public readonly int $show_logo_on_front_page,
        public readonly int $count_photos = 1,
        private mixed $logo = null,
    ) {}
    public function getLogo(): mixed {
        return $this->logo;
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'show_logo_on_first_page' => $this->show_logo_on_first_page,
            'show_logo_on_front_page' => $this->show_logo_on_front_page,
            'count_photos' => $this->count_photos,
        ];
    }
}
