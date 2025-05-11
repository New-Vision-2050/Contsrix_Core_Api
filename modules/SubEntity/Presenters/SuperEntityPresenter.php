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
        return [
            'id' => $this->superEntity['id'],
            'name' => is_array($this->superEntity['name']) ? $this->superEntity['name'][app()->getLocale()]: $this->superEntity['name'],
        ];
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }
}
