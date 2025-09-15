<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoShopDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $name,
        public ?string $description = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $websiteUrl = null,
        public ?string $facebookUrl = null,
        public ?string $instagramUrl = null,
        public ?string $twitterUrl = null,
        public ?string $tiktokUrl = null,
        public ?string $snapchatUrl = null,
        public ?string $whatsappNumber = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website_url' => $this->websiteUrl,
            'facebook_url' => $this->facebookUrl,
            'instagram_url' => $this->instagramUrl,
            'twitter_url' => $this->twitterUrl,
            'tiktok_url' => $this->tiktokUrl,
            'snapchat_url' => $this->snapchatUrl,
            'whatsapp_number' => $this->whatsappNumber,
        ];
    }
    public function getCompanyId(): UuidInterface
    {
        return $this->companyId;
    }
}
