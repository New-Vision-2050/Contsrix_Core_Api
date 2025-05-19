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
            'name' => is_array($this->superEntity['name']) ? $this->superEntity['name'][app()->getLocale()]: $this->superEntity['name'],
        ];

        if(isset($this->superEntity['allowed_attributes']) && filled($this->superEntity['allowed_attributes']))
        {
            $attributes['allowed_attributes'] = $this->superEntity['allowed_attributes'];
        }

        return $attributes;
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }
}
