<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Presenters\Dashboard;

use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoCategoryPresenter extends AbstractPresenter
{
    private EcoCategory $ecoCategory;

    public function __construct(EcoCategory $ecoCategory)
    {
        $this->ecoCategory = $ecoCategory;
    }

    protected function present(bool $isListing = false): array
    {
         $media=$this->ecoCategory->getFirstMedia('upload');
        $data = [
            'id' => $this->ecoCategory->id,
            'name' => $this->ecoCategory->name,
            "file"=>$media != null ? (new MediaPresenter($media))->getData():null,   
            'products_count' => $this->ecoCategory->products_count ?? 0,
            'parent' => $this->ecoCategory->parent
                ? (new EcoCategoryPresenter($this->ecoCategory->parent))->getData() : null,      
        ];



        return $data;
    }

}
