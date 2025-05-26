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

        $registrationConfigValues = ['registration_forms', 'is_registrable'];

        foreach ($registrationConfigValues as $value) {
            if (isset($this->superEntityConfig[$value]) && filled($this->superEntityConfig[$value])) {
                $attributes[$value] = $this->superEntityConfig[$value];
            }
        }

        return $attributes;
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }
}
