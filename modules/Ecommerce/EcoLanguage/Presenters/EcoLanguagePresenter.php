<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Presenters;

use Modules\Ecommerce\EcoLanguage\Models\EcoLanguage;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Language\Presenters\LanguagePresenter;

class EcoLanguagePresenter extends AbstractPresenter
{
    private EcoLanguage $ecoLanguage;

    public function __construct(EcoLanguage $ecoLanguage)
    {
        $this->ecoLanguage = $ecoLanguage;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoLanguage->id,
            'company_id' => $this->ecoLanguage->company_id,
            'language' => $this->ecoLanguage->language ? (new LanguagePresenter($this->ecoLanguage->language))->getData() : null,
            'is_default' => (int) $this->ecoLanguage->is_default,
            'is_active' => (int) $this->ecoLanguage->is_active,
        ];
    }
}
