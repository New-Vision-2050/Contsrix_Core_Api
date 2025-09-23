<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Presenters\Dashboard;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Ecommerce\EcoShop\Models\EcoShop;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoShopDashboardPresenter extends AbstractPresenter
{
    private EcoShop $ecoShop;

    public function __construct(EcoShop $ecoShop)
    {
        $this->ecoShop = $ecoShop;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoShop->id,
            'name' => $this->ecoShop->name,
            'description' => $this->ecoShop->description,
            'phone' => $this->ecoShop->phone,
            'email' => $this->ecoShop->email,
            'website_url' => $this->ecoShop->website_url,
            'facebook_url' => $this->ecoShop->facebook_url,
            'instagram_url' => $this->ecoShop->instagram_url,
            'twitter_url' => $this->ecoShop->twitter_url,
            'tiktok_url' => $this->ecoShop->tiktok_url,
            'snapchat_url' => $this->ecoShop->snapchat_url,
            'whatsapp_number' => $this->ecoShop->whatsapp_number,
        ];
    }
}
