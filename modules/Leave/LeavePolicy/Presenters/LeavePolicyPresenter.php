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
            'leave_type_id' => $this->leavePolicy->leave_type_id,
            'leave_type_name' => $this->leavePolicy->leaveType->name ?? null,
            'is_deduct_from_balance' => $this->leavePolicy->leaveType->is_deduct_from_balance ?? null,
            'max_balance_days' => $this->leavePolicy->max_balance_days,
            'total_days' => $this->leavePolicy->total_days,
            'min_notice_days' => $this->leavePolicy->min_notice_days,
            'day_calculation_type' => $this->leavePolicy->day_calculation_type,
            'entitlement_type' => $this->leavePolicy->entitlement_type,
            'entitlement_start_from' => $this->leavePolicy->entitlement_start_from,
            'promotion_type' => $this->leavePolicy->promotion_type,
            'promotion_duration_months' => $this->leavePolicy->promotion_duration_months,
            'is_rollover_allowed' => $this->leavePolicy->is_rollover_allowed?1:0,
            'rollover_duration_months' => $this->leavePolicy->rollover_duration_months,
            'allow_exceed_balance' => $this->leavePolicy->allow_exceed_balance?1:0,
            'max_days' => $this->leavePolicy->max_days,
        ];
    }
}
