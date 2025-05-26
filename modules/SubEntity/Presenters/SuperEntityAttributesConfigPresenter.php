<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class SuperEntityAttributesConfigPresenter extends AbstractPresenter
{
    private array $superEntityConfig;

    public function __construct(array $superEntityConfig)
    {
        $this->superEntityConfig = $superEntityConfig;
    }

    protected function present(bool $isListing = false): array
    {
        $attributes = [];

        $registrationConfigValues = ['default_attributes', 'optional_attributes'];
        foreach ($registrationConfigValues as $value) {
            if (isset($this->superEntityConfig['config'][$value]) && filled($this->superEntityConfig['config'][$value])) {
                $attributes[$value] = $this->superEntityConfig['config'][$value];
            }
        }

        return $attributes;
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }
}
