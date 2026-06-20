<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Stakeholder\Models\Stakeholder;

class StakeholderPresenter extends AbstractPresenter
{
    private Stakeholder $stakeholder;

    public function __construct(Stakeholder $stakeholder)
    {
        $this->stakeholder = $stakeholder;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->stakeholder->id,
            'name' => $this->stakeholder->name,
            'status' => $this->stakeholder->status,
            'created_at' => $this->stakeholder->created_at,
            'updated_at' => $this->stakeholder->updated_at,
        ];
    }
}
