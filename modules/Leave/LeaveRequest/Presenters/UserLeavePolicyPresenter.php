<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Modules\Leave\LeaveRequest\Models\UserLeavePolicy;

/**
 * Presents one user ↔ policy assignment together with its live yearly balance.
 *
 * Input row shape: array{assignment: UserLeavePolicy, policy: LeavePolicy, balance: array}
 */
class UserLeavePolicyPresenter extends AbstractPresenter
{
    public function __construct(private readonly array $row)
    {
    }

    protected function present(bool $isListing = false): array
    {
        /** @var UserLeavePolicy $assignment */
        $assignment = $this->row['assignment'];
        /** @var LeavePolicy $policy */
        $policy = $this->row['policy'];
        $balance = $this->row['balance'];

        return [
            'leave_policy_id' => (string) $policy->id,
            'name' => $policy->name,
            'is_default' => $assignment->is_default ? 1 : 0,
            'assigned_at' => $assignment->created_at?->toDateTimeString(),
            'leave_type_id' => (string) $policy->leave_type_id,
            'leave_type_name' => $policy->leaveType?->name,
            'requires_attachment' => (int) ($policy->leaveType?->requires_attachment ?? 0),
            'is_deduct_from_balance' => $balance['is_deduct_from_balance'] ? 1 : 0,
            'day_calculation_type' => $policy->day_calculation_type,
            'entitlement_type' => $policy->entitlement_type,
            'min_notice_days' => $policy->min_notice_days,
            'allow_exceed_balance' => $policy->allow_exceed_balance ? 1 : 0,
            'year' => $balance['year'],
            'entitled_days' => $balance['entitled_days'],
            'used_days' => $balance['used_days'],
            'pending_days' => $balance['pending_days'],
            'available_days' => $balance['available_days'],
        ];
    }
}
