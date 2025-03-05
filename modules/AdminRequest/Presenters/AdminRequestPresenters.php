<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Presenters;

use Modules\AdminRequest\Models\AdminRequest;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AdminRequestPresenter extends AbstractPresenter
{
    private AdminRequest $adminRequest;

    public function __construct(AdminRequest $adminRequest)
    {
        $this->adminRequest = $adminRequest;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->adminRequest->id,
            'name' => $this->adminRequest->name,
        ];
    }
}
