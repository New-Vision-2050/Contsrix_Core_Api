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
            'children' => $this->module->children
                ->map(fn(Module $child) => (new self($child))->present($isListing))
                ->values()
                ->all(),
        ];
    }
}
