<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Presenters;

use Modules\Ecommerce\Banner\Models\SettingPage;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SettingPagePresenter extends AbstractPresenter
{
    private SettingPage $settingPage;

    public function __construct(SettingPage $settingPage)
    {
        $this->settingPage = $settingPage;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->settingPage->id,
            'type' => $this->settingPage->type,
            'title_header' => $this->settingPage->title_header,
            'description_header' => $this->settingPage->description_header,
            'title_footer' => $this->settingPage->title_footer,
            'description_footer' => $this->settingPage->description_footer,
            'is_active' => (int) $this->settingPage->is_active,
        ];
    }
}
