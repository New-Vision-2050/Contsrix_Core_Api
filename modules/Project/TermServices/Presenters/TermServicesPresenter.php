<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Presenters;

use Modules\Project\TermServices\Models\TermServices;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TermServicesPresenter extends AbstractPresenter
{
    private TermServices $termServices;

    public function __construct(TermServices $termServices)
    {
        $this->termServices = $termServices;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->termServices->id,
            'name' => $this->termServices->name,
        ];
    }
}
