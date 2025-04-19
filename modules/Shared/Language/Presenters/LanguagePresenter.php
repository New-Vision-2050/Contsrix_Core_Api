<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Presenters;

use Modules\Shared\Language\Models\Language;
use BasePackage\Shared\Presenters\AbstractPresenter;

class LanguagePresenter extends AbstractPresenter
{
    private Language $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->language->id,
            'name' =>app()->getLocale() == 'ar' ? $this->language->lang_ar : $this->language->lang,
            'short_name'=> $this->language->iso_code,
            "status" => $this->language->is_active,
            "is_rtl" => $this->language->is_rtl
        ];
    }
}
