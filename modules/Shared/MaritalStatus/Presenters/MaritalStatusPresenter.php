<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Presenters;

use Modules\Shared\MaritalStatus\Models\MaritalStatus;
use BasePackage\Shared\Presenters\AbstractPresenter;

class MaritalStatusPresenter extends AbstractPresenter
{
    private MaritalStatus $maritalStatus;

    public function __construct(MaritalStatus $maritalStatus)
    {
        $this->maritalStatus = $maritalStatus;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->maritalStatus->id,
            'name' => $this->maritalStatus->name,
            "type" => $this->maritalStatus->type
        ];
    }
}
