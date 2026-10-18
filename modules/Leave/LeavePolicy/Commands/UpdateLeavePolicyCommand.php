<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateLeavePolicyCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?string $leave_category_id = null,
        private ?int $max_balance_days = null,
        private ?int $total_days = null,
        private ?int $min_notice_days = null,
        private ?string $day_calculation_type = null,
        private ?string $entitlement_type = null,
        private ?string $entitlement_start_from = null,
        private ?string $promotion_type = null,
        private ?int $promotion_duration_months = null,
        private bool $is_rollover_allowed = false,
        private ?int $rollover_duration_months = null,
        private bool $allow_exceed_balance = false,
        private ?int $max_days = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLeaveCategoryId(): ?string
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

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
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
        ];

        if ($this->leave_category_id !== null) {
            $data['leave_category_id'] = $this->leave_category_id;
        }

        return $data;
    }
}
