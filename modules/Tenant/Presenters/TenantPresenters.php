<?php

declare(strict_types=1);

namespace Modules\Tenant\Presenters;

use Modules\Tenant\Models\Tenant;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TenantPresenter extends AbstractPresenter
{
    private Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->tenant->id,
            'name' => $this->tenant->name,
        ];
    }
}
