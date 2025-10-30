<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Presenters;

use Modules\Ecommerce\Home\Models\Home;
use BasePackage\Shared\Presenters\AbstractPresenter;

class HomePresenter extends AbstractPresenter
{
    private Home $home;

    public function __construct(Home $home)
    {
        $this->home = $home;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->home->id,
            'name' => $this->home->name,
        ];
    }
}
