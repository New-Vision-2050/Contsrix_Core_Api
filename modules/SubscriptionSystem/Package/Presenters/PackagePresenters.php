<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Presenters;

use Modules\SubscriptionSystem\Package\Models\Package;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PackagePresenter extends AbstractPresenter
{
    private Package $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->package->id,
            'name' => $this->package->name,
        ];
    }
}
