<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Presenters;

use Modules\SubscriptionSystem\Modules\Models\Module;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ModulesPresenter extends AbstractPresenter
{
    private Module $modules;

    public function __construct(Module $modules)
    {
        $this->modules = $modules;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->modules->id,
            'name' => $this->modules->name,
            'slug' => $this->modules->slug,
            'features' => $this->modules->features->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
            ]),
            'children' => $this->modules->children->map(fn($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'features' => $child->features->map(fn($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                ]),
            ]),
        ];
    }
}
