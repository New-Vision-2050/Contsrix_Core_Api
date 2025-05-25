<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class SuperEntityRegistrationConfigPresenter extends AbstractPresenter
{
    private array $superEntityConfig;

    public function __construct(array $superEntityConfig)
    {
        $this->superEntityConfig = $superEntityConfig;
    }

    protected function present(bool $isListing = false): array
    {
        $attributes = [];

        if(isset($this->superEntityConfig['registration_forms']) && filled($this->superEntityConfig['registration_forms']))
        {
            $attributes['registration_forms'] = $this->superEntityConfig['registration_forms'];
        }

        if(isset($this->superEntityConfig['is_registrable']) && filled($this->superEntityConfig['is_registrable']))
        {
            $attributes['is_registrable'] = $this->superEntityConfig['is_registrable'];
        }

        return $attributes;
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }
}
