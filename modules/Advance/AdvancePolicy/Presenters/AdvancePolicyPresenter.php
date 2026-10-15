<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Advance\AdvancePolicy\Models\AdvancePolicy;

class AdvancePolicyPresenter extends AbstractPresenter
{
    public function __construct(private readonly AdvancePolicy $advancePolicy)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => (string) $this->advancePolicy->id,
            'name' => $this->advancePolicy->name,
            'max_amount' => (float) $this->advancePolicy->max_amount,
            'max_installments' => $this->advancePolicy->max_installments,
            'max_active_advances' => $this->advancePolicy->max_active_advances,
            'requires_attachment' => $this->advancePolicy->requires_attachment ? 1 : 0,
            'created_at' => $this->advancePolicy->created_at?->toDateTimeString(),
        ];
    }
}
