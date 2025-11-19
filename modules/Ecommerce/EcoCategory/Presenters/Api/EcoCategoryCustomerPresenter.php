<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Presenters\Api;

use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoCategoryCustomerPresenter extends AbstractPresenter
{
    private EcoCategory $ecoCategory;

    public function __construct(EcoCategory $ecoCategory)
    {
        $this->ecoCategory = $ecoCategory;
    }

    protected function present(bool $isListing = false): array
    {
        $media = $this->ecoCategory->getFirstMedia('upload');
        $data = [
            'id' => $this->ecoCategory->id,
            'name' => $this->ecoCategory->name,
            'description' => $this->ecoCategory->description,
            'file' => $media != null ? (new MediaPresenter($media))->getData() : null,
        ];

        if ($this->ecoCategory->relationLoaded('parent') && $this->ecoCategory->parent) {
            $data['parent'] = [
                'id' => $this->ecoCategory->parent->id,
                'name' => $this->ecoCategory->parent->name,
            ];
        }

        if ($this->ecoCategory->relationLoaded('children')) {
            $data['children'] = $this->ecoCategory->children->map(function ($child) {
                return (new EcoCategoryCustomerPresenter($child))->getData();
            })->values()->all();
        } 

            return $data;
        
    }
}

