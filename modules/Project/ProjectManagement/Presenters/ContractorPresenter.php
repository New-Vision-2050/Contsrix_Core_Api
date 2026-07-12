<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectManagement\Models\Contractor;

class ContractorPresenter extends AbstractPresenter
{
    private Contractor $contractor;

    public function __construct(Contractor $contractor)
    {
        $this->contractor = $contractor;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->contractor->id,
            'project_id' => $this->contractor->project_id,
            'name' => $this->contractor->name,
            'tax_card' => $this->contractor->tax_card,
            'commercial_register' => $this->contractor->commercial_register,
            'activity' => $this->contractor->activity,
            'email' => $this->contractor->email,
            'country_id' => $this->contractor->country_id,
            'country' => $this->contractor->relationLoaded('country') && $this->contractor->country ? [
                'id' => $this->contractor->country->id,
                'name' => $this->contractor->country->name,
            ] : null,
            'logo' => $this->contractor->getFirstMediaUrl('logo'),
            'project_contractor_id' => $this->contractor->project_contractor_id,
            'project_manager_name' => $this->contractor->project_manager_name,
            'project_manager_phone' => $this->contractor->project_manager_phone,
            'project_manager_nationality' => $this->contractor->project_manager_nationality,
            'project_manager_email' => $this->contractor->project_manager_email,
            'representatives' => $this->contractor->relationLoaded('representatives') ? $this->contractor->representatives->map(fn ($representative) => [
                'id' => $representative->id,
                'name' => $representative->name,
                'mobile' => $representative->mobile,
                'nationality' => $representative->nationality,
            ])->values()->all() : [],
            'created_at' => $this->contractor->created_at?->toDateTimeString(),
            'updated_at' => $this->contractor->updated_at?->toDateTimeString(),
        ];
    }
}
