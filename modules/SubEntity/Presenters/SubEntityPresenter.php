<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use Modules\SubEntity\Models\SubEntity;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SubEntityPresenter extends AbstractPresenter
{
    private SubEntity $subEntity;

    public function __construct(SubEntity $subEntity)
    {
        $this->subEntity = $subEntity;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->subEntity->id,
            'name' => $this->subEntity->name,
        ];
    }
}
