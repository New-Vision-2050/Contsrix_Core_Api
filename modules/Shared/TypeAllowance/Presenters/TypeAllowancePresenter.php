<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Presenters;

use Modules\Shared\TypeAllowance\Models\TypeAllowance;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TypeAllowancePresenter extends AbstractPresenter
{
    private TypeAllowance $typeAllowance;

    public function __construct(TypeAllowance $typeAllowance)
    {
        $this->typeAllowance = $typeAllowance;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->typeAllowance->id,
            'name' => $this->typeAllowance->name,
            'code' => $this->typeAllowance->code,
        ];
    }
}
