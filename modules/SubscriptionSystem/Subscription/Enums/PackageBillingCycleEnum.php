<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Enums;

enum PackageBillingCycleEnum: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Weekly = 'weekly';
    case OneTime = 'one-time';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
            self::Weekly => 'Weekly',
            self::OneTime => 'One Time',
        };
    }
}
