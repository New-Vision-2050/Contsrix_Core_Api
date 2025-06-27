<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Presenters;

use Modules\Subscription\Module\Models\Module;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ModulePresenter extends AbstractPresenter
{
    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->module->id,
            'name' => $this->module->name,
        ];
    }
}
