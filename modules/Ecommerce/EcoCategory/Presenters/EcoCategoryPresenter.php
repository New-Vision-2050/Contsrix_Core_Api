<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Presenters;

use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoCategoryPresenter extends AbstractPresenter
{
    private EcoCategory $ecoCategory;

    public function __construct(EcoCategory $ecoCategory)
    {
        $this->ecoCategory = $ecoCategory;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoCategory->id,
            'name' => $this->ecoCategory->name,
            'description' => $this->ecoCategory->description,
            'parent' => $this->ecoCategory->parent
                ? [
                    'id' => $this->ecoCategory->parent->id,
                    'name' => $this->ecoCategory->parent->name,
                ]
                : null,
        ];
    }
}
