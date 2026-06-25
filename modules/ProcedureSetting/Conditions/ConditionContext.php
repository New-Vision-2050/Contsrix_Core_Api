<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Conditions;

/**
 * Immutable bag carrying every piece of data a condition evaluator might need.
 *
 * Passed to every ConditionEvaluator::evaluate() call so evaluators don't
 * depend on method signatures that change every time a new condition is added.
 *
 * This is a shared DTO — any module (EmployeeTask, ClientRequest, etc.) builds
 * it from its own request data and passes it to ConditionEvaluationService.
 */
final class ConditionContext
{
    public function __construct(
        public readonly string  $userId,
        public readonly string  $companyId,
        public readonly ?string $branchId,
        public readonly ?float  $currentLatitude = null,
        public readonly ?float  $currentLongitude = null,
        public readonly ?float  $taskLatitude = null,
        public readonly ?float  $taskLongitude = null,
        public readonly ?float  $durationHours = null,
        public readonly ?string $taskDate = null,
    ) {}
}
