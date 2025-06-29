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
            'is_active' => $this->programSystem->is_active,
            'features' => $this->programSystem->features->map(fn($feature) => [
                'id' => $feature->id,
                'name' => $feature->name,
                'program_id' => $feature->pivot->program_id,
            ]),
            'company_fields' => $this->programSystem->companyFields->map(fn($field) => [
                'id' => $field->id,
                'name' => $field->name,
            ]),

            'business_types' => $this->programSystem->businessTypes->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->name,
            ]),
        ];
    }
}
