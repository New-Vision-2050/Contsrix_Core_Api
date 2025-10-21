<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Presenters\Dashboard;

use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoCategoryDashboardPresenter extends AbstractPresenter
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
            'name' => $this->ecoCategory->name, // Current locale translation
            'name_ar' => $this->ecoCategory->getTranslation('name', 'ar'),
            'name_en' => $this->ecoCategory->getTranslation('name', 'en'),
            'priority' => $this->ecoCategory->priority ?? 0,
            'is_active'=>$this->ecoCategory->is_active,
            "file"=>$media != null ? (new MediaPresenter($media))->getData():null,     
            'parent' => $this->ecoCategory->parent
                ? [
                    'id' => $this->ecoCategory->parent->id,
                    'name' => $this->ecoCategory->parent->name,
                    'priority' => $this->ecoCategory->parent->priority ?? 0,
                    
                    // Add grandparent (first parent) if exists
                    'parent' => $this->ecoCategory->parent->parent
                        ? [
                            'id' => $this->ecoCategory->parent->parent->id,
                            'name' => $this->ecoCategory->parent->parent->name,
                            'priority' => $this->ecoCategory->parent->parent->priority ?? 0,
                        ]
                        : null,
                ]
                : null,
                
        ];



        return $data;
    }

}
