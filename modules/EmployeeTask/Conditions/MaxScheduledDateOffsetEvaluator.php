<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Carbon\Carbon;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\User\Models\User;

final class MaxScheduledDateOffsetEvaluator implements ConditionEvaluator
{
    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::MaxScheduledDateOffset;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        if (! ($conditionData['is_active'] ?? false)) {
            return null;
        }

        if ($ctx->taskDate === null) {
            return null;
        }

        $settings = $conditionData['settings'] ?? [];
        $mode     = $settings['mode'] ?? 'max_task_date';

        if ($mode === 'max_task_date') {
            $maxDays = (int) ($settings['max_days'] ?? 30);
            $limit   = Carbon::today()->addDays($maxDays);
            $date    = Carbon::parse($ctx->taskDate)->startOfDay();

            if ($date->gt($limit)) {
                return new ConditionResult(
                    key: $this->condition()->value,
                    labelAr: $this->condition()->labelAr(),
                    passed: false,
                    message: "The task date cannot be more than {$maxDays} days from today.",
                    exception: 'taskDateTooFarInFuture',
                    context: ['maxDays' => $maxDays],
                );
            }

            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: true,
            );
        }

        if ($mode === 'end_contract') {
            $user = User::with('companyUser.employmentContract.contractDurationUnit')
                ->find($ctx->userId);

            $contract = $user?->companyUser?->employmentContract;

            if ($contract === null || $contract->start_date === null) {
                return null;
            }

            $endDate  = Carbon::parse($contract->start_date);
            $duration = (int) $contract->contract_duration;

            $unit = $contract->contractDurationUnit;
            if ($unit !== null) {
                match ($unit->code ?? null) {
                    'day'   => $endDate->addDays($duration),
                    'month' => $endDate->addMonths($duration),
                    'year'  => $endDate->addYears($duration),
                    default => null,
                };
            }

            $taskDateCarbon = Carbon::parse($ctx->taskDate)->startOfDay();

            if ($taskDateCarbon->gt($endDate)) {
                return new ConditionResult(
                    key: $this->condition()->value,
                    labelAr: $this->condition()->labelAr(),
                    passed: false,
                    message: 'The task date cannot be after your employment contract end date.',
                    exception: 'taskDateExceedsContractEndDate',
                );
            }

            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: true,
            );
        }

        return null;
    }
}
