<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Presenters;

use Modules\Shared\TypePrivilege\Models\TypePrivilege;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TypePrivilegePresenter extends AbstractPresenter
{
    private TypePrivilege $typePrivilege;

    public function __construct(TypePrivilege $typePrivilege)
    {
        $this->typePrivilege = $typePrivilege;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->typePrivilege->id,
            'name' => $this->typePrivilege->name,
        ];
    }
}
