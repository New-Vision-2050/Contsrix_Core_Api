<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Presenters;

use Modules\Shared\Privilege\Models\Privilege;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PrivilegePresenter extends AbstractPresenter
{
    private Privilege $privilege;

    public function __construct(Privilege $privilege)
    {
        $this->privilege = $privilege;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->privilege->id,
            'name' => $this->privilege->name,
            'type' => $this->privilege->type,
        ];
    }
}
