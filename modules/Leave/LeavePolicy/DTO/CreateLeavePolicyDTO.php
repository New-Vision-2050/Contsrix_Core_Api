<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateLeavePolicyDTO
{
    public function __construct(
        public readonly string $name,
        public readonly UuidInterface $leave_type_id,
        public readonly UuidInterface $leave_category_id,
        public readonly ?int $max_balance_days = null,
        public readonly ?int $total_days = null,
        public readonly ?int $min_notice_days = null,
        public readonly ?string $day_calculation_type = null,
        public readonly ?string $entitlement_type = null,
        public readonly ?string $entitlement_start_from = null,
        public readonly ?string $promotion_type = null,
        public readonly ?int $promotion_duration_months = null,
        public readonly bool $is_rollover_allowed = false,
        public readonly ?int $rollover_duration_months = null,
        public readonly bool $allow_exceed_balance = false,
        public readonly ?int $max_days = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'leave_type_id' => $this->leave_type_id->toString(),
            'leave_category_id' => $this->leave_category_id->toString(),
            'max_balance_days' => $this->max_balance_days,
            'total_days' => $this->total_days,
            'min_notice_days' => $this->min_notice_days,
            'day_calculation_type' => $this->day_calculation_type,
            'entitlement_type' => $this->entitlement_type,
            'entitlement_start_from' => $this->entitlement_start_from,
            'promotion_type' => $this->promotion_type,
            'promotion_duration_months' => $this->promotion_duration_months,
            'is_rollover_allowed' => $this->is_rollover_allowed,
            'rollover_duration_months' => $this->rollover_duration_months,
            'allow_exceed_balance' => $this->allow_exceed_balance,
            'max_days' => $this->max_days,
            "company_id" => tenant("id"),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLeaveTypeId(): UuidInterface
    {
        return $this->leave_type_id;
    }

    public function getLeaveCategoryId(): UuidInterface
    {
        return $this->leave_category_id;
    }

    public function getMaxBalanceDays(): ?int
    {
        return $this->max_balance_days;
    }

    public function getTotalDays(): ?int
    {
        return $this->total_days;
    }

    public function getMinNoticeDays(): ?int
    {
        return $this->min_notice_days;
    }

    public function getDayCalculationType(): ?string
    {
        return $this->day_calculation_type;
    }

    public function getEntitlementType(): ?string
    {
        return $this->entitlement_type;
    }

    public function getEntitlementStartFrom(): ?string
    {
        return $this->entitlement_start_from;
    }

    public function getPromotionType(): ?string
    {
        return $this->promotion_type;
    }

    public function getPromotionDurationMonths(): ?int
    {
        return $this->promotion_duration_months;
    }

    public function getIsRolloverAllowed(): bool
    {
        return $this->is_rollover_allowed;
    }

    public function getRolloverDurationMonths(): ?int
    {
        return $this->rollover_duration_months;
    }

    public function getAllowExceedBalance(): bool
    {
        return $this->allow_exceed_balance;
    }

    public function getMaxDays(): ?int
    {
        return $this->max_days;
    }
}
