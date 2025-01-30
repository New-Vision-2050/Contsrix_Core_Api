<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SettingPresenter extends AbstractPresenter
{
    private Setting $setting;

    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'key' => $this->setting->key,
            'value' => $this->setting->value,
        ];
    }
}
