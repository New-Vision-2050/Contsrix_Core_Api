<?php

declare(strict_types=1);

namespace Modules\Reports\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class ReportLookupPresenter extends AbstractPresenter
{
    public function __construct(private array $lookups)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return $this->lookups;
    }
}
