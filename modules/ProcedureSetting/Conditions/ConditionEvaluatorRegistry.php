<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Conditions;

use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

/**
 * Registry that maps InternalProcessCondition enum cases to their evaluator instances.
 *
 * Each module creates its own registry instance with its own evaluators.
 * The registry is then passed to ConditionEvaluationService for dispatch.
 */
final class ConditionEvaluatorRegistry
{
    /** @var array<string, ConditionEvaluator> */
    private array $evaluators = [];

    /**
     * @param iterable<ConditionEvaluator> $evaluators
     */
    public function __construct(iterable $evaluators = [])
    {
        foreach ($evaluators as $evaluator) {
            $this->register($evaluator);
        }
    }

    public function register(ConditionEvaluator $evaluator): void
    {
        $this->evaluators[$evaluator->condition()->value] = $evaluator;
    }

    public function has(InternalProcessCondition $condition): bool
    {
        return isset($this->evaluators[$condition->value]);
    }

    public function get(InternalProcessCondition $condition): ?ConditionEvaluator
    {
        return $this->evaluators[$condition->value] ?? null;
    }

    /**
     * Get all registered evaluators.
     *
     * @return array<string, ConditionEvaluator>
     */
    public function all(): array
    {
        return $this->evaluators;
    }

    /**
     * Get evaluators for the given form, filtered by form group.
     *
     * @return array<string, ConditionEvaluator>
     */
    public function forFormGroup(string $formGroup): array
    {
        return array_filter(
            $this->evaluators,
            static fn (ConditionEvaluator $e): bool => $e->condition()->formGroup() === $formGroup,
        );
    }
}
