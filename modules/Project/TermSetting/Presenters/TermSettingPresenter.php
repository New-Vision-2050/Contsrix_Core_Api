<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Presenters;

use Modules\Project\TermSetting\Models\TermSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TermSettingPresenter extends AbstractPresenter
{
    private TermSetting $termSetting;

    public function __construct(TermSetting $termSetting)
    {
        $this->termSetting = $termSetting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->termSetting->id,
            'name' => $this->termSetting->name,
        ];
    }
}
