<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Presenters;

use Modules\Leave\LeaveType\Models\LeaveType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class LeaveTypePresenter extends AbstractPresenter
{
    private LeaveType $leaveType;

    public function __construct(LeaveType $leaveType)
    {
        $this->leaveType = $leaveType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->leaveType->id,
            'name' => $this->leaveType->name,
            'is_payed' => $this->leaveType->is_payed?1:0,
            'is_deduct_from_balance' => $this->leaveType->is_deduct_from_balance?1:0,
            'conditions' => $this->leaveType->conditions,
        ];
    }
}
