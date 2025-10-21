<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Presenters\Dashboard;

use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoBrandDashboardPresenter extends AbstractPresenter
{
    private EcoBrand $ecoBrand;

    public function __construct(EcoBrand $ecoBrand)
    {
        $this->ecoBrand = $ecoBrand;
    }

    protected function present(bool $isListing = false): array
    {
        $media = $this->ecoBrand->getFirstMedia('upload');
        
        return [
            'id' => $this->ecoBrand->id,
            'name' => $this->ecoBrand->name, // Current locale translation
            'name_ar' => $this->ecoBrand->getTranslation('name', 'ar'),
            'name_en' => $this->ecoBrand->getTranslation('name', 'en'),
            'description' => $this->ecoBrand->description,
            'description_ar' => $this->ecoBrand->getTranslation('description', 'ar'),
            'description_en' => $this->ecoBrand->getTranslation('description', 'en'),
            'is_active'=> (int) $this->ecoBrand->is_active,
            // Brand images
            "file" => $media != null ? (new MediaPresenter($media))->getData() : null,
        ];
    }
}
