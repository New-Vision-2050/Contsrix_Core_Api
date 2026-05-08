<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Ensures each user in {@see $value} is not already an action taker on another step
 * for the same {@see $procedureSettingId} (optionally ignoring {@see $ignoreStepId} on update).
 */
class ActionTakerUserIdsUniquePerProcedureSetting implements ValidationRule
{
    public function __construct(
        private readonly string $procedureSettingId,
        private readonly ?int $ignoreStepId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $value === []) {
            return;
        }

        $userIds = array_values(array_unique(array_filter(
            $value,
            static fn ($id): bool => is_string($id) && $id !== '',
        )));

        if ($userIds === []) {
            return;
        }

        $query = DB::table('procedure_setting_step_action_takers as at')
            ->join('procedure_setting_steps as s', 's.id', '=', 'at.procedure_setting_step_id')
            ->where('s.procedure_setting_id', $this->procedureSettingId)
            ->whereIn('at.user_id', $userIds);

        if ($this->ignoreStepId !== null) {
            $query->where('s.id', '!=', $this->ignoreStepId);
        }

        if (function_exists('tenancy') && tenancy()->initialized && tenant('id')) {
            $query->where('s.company_id', (string) tenant('id'));
        }

        if ($query->exists()) {
            $fail('One or more users are already action takers on another step for this procedure setting.');
        }
    }
}
