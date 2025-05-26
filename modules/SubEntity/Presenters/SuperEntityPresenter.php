<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class SuperEntityPresenter extends AbstractPresenter
{
    private array $superEntity;

    public function __construct(array $superEntity)
    {
        $this->superEntity = $superEntity;
    }

    protected function present(bool $isListing = false): array
    {
        $attributes = [
            'id' => $this->superEntity['id'],
            'name' => is_array($this->superEntity['name']) ? $this->superEntity['name'][app()->getLocale()] : $this->superEntity['name'],
        ];

        $configSet = ['default_attributes', 'optional_attributes', 'registration_forms', 'is_registrable'];

        foreach ($configSet as $value) {
            if (isset($this->superEntity['config'][$value]) && filled($this->superEntity['config'][$value])) {
                $attributes[$value] = $this->superEntity['config'][$value];
            }
        }

        return $attributes;
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }
}
