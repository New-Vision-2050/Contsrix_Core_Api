<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Advance\AdvancePolicy\Models\AdvancePolicy;
use Modules\Advance\AdvanceRequest\Models\UserAdvancePolicy;

/**
 * Presents one user ↔ policy assignment together with its live availability.
 *
 * Input row shape: array{assignment: UserAdvancePolicy, policy: AdvancePolicy, availability: array}
 */
class UserAdvancePolicyPresenter extends AbstractPresenter
{
    public function __construct(private readonly array $row)
    {
    }

    protected function present(bool $isListing = false): array
    {
        /** @var UserAdvancePolicy $assignment */
        $assignment = $this->row['assignment'];
        /** @var AdvancePolicy $policy */
        $policy = $this->row['policy'];
        $availability = $this->row['availability'];

        return [
            'advance_policy_id' => (string) $policy->id,
            'name' => $policy->name,
            'is_default' => $assignment->is_default ? 1 : 0,
            'assigned_at' => $assignment->created_at?->toDateTimeString(),
            'max_amount' => (float) $policy->max_amount,
            'max_installments' => $policy->max_installments,
            'max_active_advances' => $policy->max_active_advances,
            'requires_attachment' => $policy->requires_attachment ? 1 : 0,
            'active_count' => $availability['active_count'],
            'pending_amount' => $availability['pending_amount'],
            'active_amount' => $availability['active_amount'],
            'remaining_slots' => $availability['remaining_slots'],
            'can_request' => $availability['can_request'] ? 1 : 0,
        ];
    }
}
