<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Presenters;

use Modules\Leave\LeavePolicyCategory\Models\LeavePolicyCategory;
use BasePackage\Shared\Presenters\AbstractPresenter;

class LeavePolicyCategoryPresenter extends AbstractPresenter
{
    private LeavePolicyCategory $leavePolicyCategory;

    public function __construct(LeavePolicyCategory $leavePolicyCategory)
    {
        $this->leavePolicyCategory = $leavePolicyCategory;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->leavePolicyCategory->id,
            'name' => $this->leavePolicyCategory->name,
        ];
    }
}
