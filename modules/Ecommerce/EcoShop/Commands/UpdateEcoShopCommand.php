<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoShopCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $name = null,
        private ?string $description = null,
        private ?string $phone = null,
        private ?string $email = null,
        private ?string $websiteUrl = null,
        private ?string $facebookUrl = null,
        private ?string $instagramUrl = null,
        private ?string $twitterUrl = null,
        private ?string $tiktokUrl = null,
        private ?string $snapchatUrl = null,
        private ?string $whatsappNumber = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->email !== null) $data['email'] = $this->email;
        if ($this->websiteUrl !== null) $data['website_url'] = $this->websiteUrl;
        if ($this->facebookUrl !== null) $data['facebook_url'] = $this->facebookUrl;
        if ($this->instagramUrl !== null) $data['instagram_url'] = $this->instagramUrl;
        if ($this->twitterUrl !== null) $data['twitter_url'] = $this->twitterUrl;
        if ($this->tiktokUrl !== null) $data['tiktok_url'] = $this->tiktokUrl;
        if ($this->snapchatUrl !== null) $data['snapchat_url'] = $this->snapchatUrl;
        if ($this->whatsappNumber !== null) $data['whatsapp_number'] = $this->whatsappNumber;
        return $data;
    }
}
