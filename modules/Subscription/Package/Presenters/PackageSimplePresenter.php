<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Presenters;

use Modules\Subscription\Package\Models\Package;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PackageSimplePresenter extends AbstractPresenter
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

    public function getData(bool $isListing = false): ?array
    {
        $array = $this->present($isListing);
        foreach ($array as $key => $value) {
            if ($value === 'delete_this_row') {
                unset($array[$key]);
            }
            if ($value === 'delete_this_array') {
                return null;
            }
        }
        return $array;
    }
}
