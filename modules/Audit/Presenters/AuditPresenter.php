<?php

declare(strict_types=1);

namespace Modules\Audit\Presenters;

use Modules\Audit\Models\Audit;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AuditPresenter extends AbstractPresenter
{
    private Audit $audit;

    public function __construct(Audit $audit)
    {
        $this->audit = $audit;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->audit->id,
            'name' => $this->audit->name,
        ];
    }
}
