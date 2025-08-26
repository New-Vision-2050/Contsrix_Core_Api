<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Presenters;

use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use BasePackage\Shared\Presenters\AbstractPresenter;

class LeavePolicyPresenter extends AbstractPresenter
{
    private LeavePolicy $leavePolicy;

    public function __construct(LeavePolicy $leavePolicy)
    {
        $this->leavePolicy = $leavePolicy;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->leavePolicy->id,
            'name' => $this->leavePolicy->name,
            'total_days' => $this->leavePolicy->total_days,
            'day_type' => $this->leavePolicy->day_type,
            'is_rollover_allowed' => $this->leavePolicy->is_rollover_allowed?1:0,
            'max_days_per_request' => $this->leavePolicy->max_days_per_request,
            'upgrade_condition' => $this->leavePolicy->upgrade_condition,
            'is_allow_half_day' => $this->leavePolicy->is_allow_half_day?1:0,
        ];
    }
}
