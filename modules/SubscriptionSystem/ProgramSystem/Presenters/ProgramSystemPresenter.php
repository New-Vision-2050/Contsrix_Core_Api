<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Presenters;

use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProgramSystemPresenter extends AbstractPresenter
{
    private ProgramSystem $programSystem;

    public function __construct(ProgramSystem $programSystem)
    {
        $this->programSystem = $programSystem;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->programSystem->id,
            'name' => $this->programSystem->name,
            'features' => $this->programSystem->features->map(fn($feature) => [
                'id' => $feature->id,
                'name' => $feature->name,
                'module_id' => $feature->pivot->module_id,
            ])
        ];
    }
}
