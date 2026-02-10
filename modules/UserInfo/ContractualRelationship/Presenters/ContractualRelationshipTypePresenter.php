<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Presenters;

use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationshipType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ContractualRelationshipTypePresenter extends AbstractPresenter
{
    private ContractualRelationshipType $type;

    public function __construct(ContractualRelationshipType $type)
    {
        $this->type = $type;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->type->id,
            'name' => $this->type->name,
            'is_active' => $this->type->is_active?1:0,
        ];
    }
}
