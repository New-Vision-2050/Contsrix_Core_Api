<?php

declare(strict_types=1);

namespace Modules\Setting\Presenters;

use Modules\Setting\Models\Driver;
use Modules\Setting\Models\IdentifierSetting;
use Modules\Setting\Models\LoginWayStep;
use Modules\Setting\Models\Setting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class IdentifierPresenter extends AbstractPresenter
{

    public function __construct(public IdentifierSetting $identifier)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'name' => $this->identifier->name,
            'default' => $this->identifier->default,
        ];
    }
}
