<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Presenters;

use Modules\Ecommerce\EcoClient\Models\EcoClient;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoClientPresenter extends AbstractPresenter
{
    private EcoClient $ecoClient;

    public function __construct(EcoClient $ecoClient)
    {
        $this->ecoClient = $ecoClient;
    }

    protected function present(bool $isListing = false): array
    {
        $firstMedia = $this->ecoClient->getFirstMedia('eco_profile_client_image');
        return [
            'id' => $this->ecoClient->id,
            'name' => $this->ecoClient->name,
            'email' => $this->ecoClient->email,
            'phone_code' => $this->ecoClient->phone_code,
            'phone' => $this->ecoClient->phone,
            'profile_image' => $firstMedia ? (new MediaPresenter($firstMedia))->getData() : null,

        ];
    }
}
